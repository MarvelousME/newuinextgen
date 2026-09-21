#!/usr/bin/env python3
"""Build PRODUCTION installable theme/plugin ZIPs (PclZip-safe). Port of build-production-release.ps1."""
from __future__ import annotations

import hashlib
import json
import os
import re
import shutil
import subprocess
import sys
import tempfile
import time
import zipfile
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
PROD = ROOT / "PRODUCTION"
PKG = PROD / "01-INSTALLABLE-PACKAGES"
OPT = PKG / "optional"
DROP = PKG / "drop-ins"
VAL = PROD / "04-VALIDATION"

EXCLUDE_DIRS = {
    ".git",
    "node_modules",
    "vendor",
    "build-src",
    "tests",
    ".cursor",
    "offline-packages",
    "__pycache__",
    "_extracted",
}


def wp_header_version(path: Path, fallback: str) -> str:
    if not path.is_file():
        return fallback
    text = path.read_text(encoding="utf-8", errors="ignore")
    m = re.search(r"(?m)^\s*\*?\s*Version:\s*([0-9.]+)", text)
    return m.group(1) if m else fallback


def copy_filtered(src: Path, dst: Path, extra_exclude: set[str] | None = None) -> None:
    if dst.exists():
        shutil.rmtree(dst)
    xd = EXCLUDE_DIRS | (extra_exclude or set())
    def ignore(directory: str, names: list[str]) -> set[str]:
        skipped = set()
        for name in names:
            p = Path(directory) / name
            if name in xd or name.endswith(".zip") or name.endswith(".map"):
                skipped.add(name)
            elif name.startswith(".env"):
                skipped.add(name)
            elif p.is_file() and name in {".DS_Store"}:
                skipped.add(name)
        return skipped

    shutil.copytree(src, dst, ignore=ignore)


def make_zip(stage_parent: Path, root_folder: str, zip_path: Path) -> None:
    if zip_path.exists():
        zip_path.unlink()
    zip_path.parent.mkdir(parents=True, exist_ok=True)
    root_path = stage_parent / root_folder
    if not root_path.is_dir():
        raise SystemExit(f"missing staged folder: {root_path}")

    with zipfile.ZipFile(zip_path, "w", compression=zipfile.ZIP_DEFLATED, allowZip64=True) as zf:
        zf.writestr(root_folder.replace("\\", "/") + "/", b"")
        for dirpath, dirnames, filenames in os.walk(root_path):
            dirnames[:] = sorted(d for d in dirnames if d not in EXCLUDE_DIRS)
            for filename in sorted(filenames):
                if filename.endswith(".zip") or filename in {".env", ".DS_Store"}:
                    continue
                abs_path = Path(dirpath) / filename
                rel = abs_path.relative_to(stage_parent).as_posix()
                st = abs_path.stat()
                info = zipfile.ZipInfo(rel, date_time=time.localtime(st.st_mtime)[:6])
                info.compress_type = zipfile.ZIP_DEFLATED
                info.create_system = 0
                info.external_attr = 0o644 << 16
                with open(abs_path, "rb") as fh:
                    zf.writestr(info, fh.read())

    needle = "style.css" if root_folder in {"NextGenTutors-BeyondInfinity", "hello-elementor", "NextgenTutors-TutorFabulous"} else f"{root_folder}/"
    with zipfile.ZipFile(zip_path) as z:
        hits = [i for i in z.infolist() if i.filename.replace("\\", "/").endswith(needle)]
        if not hits:
            raise SystemExit(f"missing {needle} in {zip_path}")
        info = hits[0]
        if info.flag_bits & 0x08:
            raise SystemExit(f"data-descriptor flag set on {info.filename}")
        if "\\" in info.filename:
            raise SystemExit(f"backslash entry name: {info.filename!r}")
        print(f"ok {info.filename} flag_bits={info.flag_bits}")


def build_plugin(stage_root: Path, name: str, src_rel: str, entry: str, dest_dir: Path, zip_name: str | None = None) -> dict:
    src = ROOT / src_rel
    if not src.is_dir():
        raise SystemExit(f"Missing plugin source {src}")
    parent = stage_root / name
    stage = parent / name
    copy_filtered(src, stage)
    ver = wp_header_version(stage / entry, "0.0.0")
    out_name = zip_name or f"{name}-v{ver}.zip"
    zip_path = dest_dir / out_name
    make_zip(parent, name, zip_path)
    print(f"Built {zip_path}")
    return {"name": name, "version": ver, "path": str(zip_path), "entry": entry}


def ensure_hello_elementor(stage_root: Path) -> Path | None:
    """Stage Hello Elementor from local copy or download from wordpress.org."""
    local_candidates = [
        ROOT / "docker" / "hello-elementor",
        ROOT / "hello-elementor",
    ]
    for cand in local_candidates:
        if (cand / "style.css").is_file():
            parent = stage_root / "hello"
            stage = parent / "hello-elementor"
            copy_filtered(cand, stage, extra_exclude={".git", "node_modules"})
            return parent

    # Download official Hello Elementor (parent theme required for child)
    import urllib.request

    tmp_zip = stage_root / "hello-elementor-download.zip"
    url = "https://downloads.wordpress.org/theme/hello-elementor.latest-stable.zip"
    print(f"Downloading Hello Elementor from {url}")
    urllib.request.urlretrieve(url, tmp_zip)
    extract_to = stage_root / "hello-dl"
    extract_to.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(tmp_zip) as zf:
        zf.extractall(extract_to)
    # wordpress.org zip root is hello-elementor/
    extracted = extract_to / "hello-elementor"
    if not extracted.is_dir():
        # fallback: first directory
        dirs = [p for p in extract_to.iterdir() if p.is_dir()]
        if not dirs:
            raise SystemExit("Hello Elementor download did not contain a theme folder")
        extracted = dirs[0]
    parent = stage_root / "hello"
    stage = parent / "hello-elementor"
    if stage.exists():
        shutil.rmtree(stage)
    parent.mkdir(parents=True, exist_ok=True)
    shutil.move(str(extracted), str(stage))
    return parent


def main() -> int:
    for d in (PROD, PKG, OPT, DROP, VAL):
        d.mkdir(parents=True, exist_ok=True)

    # Clear previous package zips so we don't mix old hashes
    for old in PKG.rglob("*.zip"):
        old.unlink()

    with tempfile.TemporaryDirectory(prefix="ngt-prod-") as tmp:
        stage_root = Path(tmp)

        # Theme: prefer branded TutorFabulous package if present, else monorepo root
        theme_src_pkg = ROOT / "NextgenTutors-TutorFabulous"
        theme_stage_parent = stage_root / "theme"
        # Keep ZIP root NextGenTutors-BeyondInfinity for install docs + fallback activate,
        # while content comes from the live branded theme sources.
        theme_root_name = "NextGenTutors-BeyondInfinity"
        theme_stage = theme_stage_parent / theme_root_name
        theme_stage.mkdir(parents=True, exist_ok=True)

        if theme_src_pkg.is_dir() and (theme_src_pkg / "style.css").is_file():
            copy_filtered(theme_src_pkg, theme_stage, extra_exclude={".git", "node_modules", "tests", "_extracted"})
        else:
            for dname in ("assets", "inc", "templates", "template-parts", "page-templates", "prototypes", "content"):
                src = ROOT / dname
                if src.is_dir():
                    copy_filtered(src, theme_stage / dname, extra_exclude={".git", "node_modules", "tests", "_extracted"})
            ui_src = ROOT / "ui-library"
            if ui_src.is_dir():
                copy_filtered(ui_src, theme_stage / "ui-library", extra_exclude={".git", "node_modules", "tests"})
            page_files = list(ROOT.glob("page-*.php"))
            root_files = [
                "style.css",
                "functions.php",
                "header.php",
                "footer.php",
                "index.php",
                "front-page.php",
                "home.php",
                "page.php",
                "single.php",
                "single-tutors.php",
                "archive.php",
                "archive-tutors.php",
                "searchform.php",
                "comments.php",
                "404.php",
                "admin-dashboard.php",
                "screenshot.png",
            ] + [p.name for p in page_files]
            for fname in dict.fromkeys(root_files):
                src = ROOT / fname
                if src.is_file():
                    shutil.copy2(src, theme_stage / fname)

        extracted = theme_stage / "content" / "_extracted"
        if extracted.exists():
            shutil.rmtree(extracted)

        theme_version = wp_header_version(theme_stage / "style.css", "1.9.29")
        theme_zip = PKG / f"NextGenTutors-BeyondInfinity-v{theme_version}.zip"
        make_zip(theme_stage_parent, theme_root_name, theme_zip)
        print(f"Built theme {theme_zip}")

        hello_parent = ensure_hello_elementor(stage_root)
        if hello_parent:
            hello_ver = wp_header_version(hello_parent / "hello-elementor" / "style.css", "3.5.1")
            hello_zip = PKG / f"Hello-Elementor-v{hello_ver}.zip"
            make_zip(hello_parent, "hello-elementor", hello_zip)
            print(f"Built parent {hello_zip}")

        plugins = [
            ("NextGenTutors-Companion", "NextGenTutors-Companion", "nextgencompanion.php", PKG, None),
            ("NextGenTutors-Plugin-Manager", "NextGenTutors-Plugin-Manager", "NextGenTutors-Plugin-Manager.php", PKG, None),
            ("NextGenTutors-Mission-Control", "NextGenTutors-Mission-Control", "nextgentutors-mission-control.php", PKG, None),
            ("nextgen-3d-scroll-manager", "nextgen-3d-scroll-manager", "nextgen-3d-scroll-manager.php", PKG, None),
            ("nextgen-3d-filmstrip", "nextgen-3d-filmstrip", "nextgen-3d-filmstrip.php", PKG, None),
            ("nextgen-subjects-widget", "nextgen-subjects-widget", "nextgen-subjects-widget.php", PKG, None),
            ("NextGenTutors-Html-Importer", "NextGenTutors-Html-Importer", "revamp-html-importer.php", PKG, None),
            ("NextGenTutors-AI-Integration", "NextGenTutors-AI-Integration", "nextgentutors-ai-integration.php", OPT, None),
            ("NextGenTutors-BeyondMeasure", "NextGenTutors-BeyondMeasure", "nextgentutors-beyond-measure.php", OPT, None),
            ("nextgen-automation-hub", "nextgen-automation-hub", "nextgen-automation-hub.php", OPT, None),
        ]
        for name, src_rel, entry, dest, zname in plugins:
            build_plugin(stage_root, name, src_rel, entry, dest, zname)

        # UI library drop-in
        ui_src = ROOT / "ui-library"
        if ui_src.is_dir():
            ui_parent = stage_root / "ui-drop"
            ui_stage = ui_parent / "ngt-ui-library"
            copy_filtered(ui_src, ui_stage, extra_exclude={".git", "node_modules", "tests"})
            ui_zip = DROP / "ngt-ui-library.zip"
            make_zip(ui_parent, "ngt-ui-library", ui_zip)
            print(f"Built drop-in {ui_zip}")

    # Checksums
    checksum_file = PROD / "CHECKSUMS.sha256"
    lines = []
    for zpath in sorted(PKG.rglob("*.zip")):
        h = hashlib.sha256(zpath.read_bytes()).hexdigest()
        rel = zpath.relative_to(PROD).as_posix()
        lines.append(f"{h}  {rel}")
    checksum_file.write_text("\n".join(lines) + "\n", encoding="ascii")

    try:
        git_sha = subprocess.check_output(["git", "-C", str(ROOT), "rev-parse", "HEAD"], text=True).strip()
    except Exception:
        git_sha = ""

    manifest = {
        "schema_version": "1.0.0",
        "release_train": datetime.now(timezone.utc).strftime("%Y.%m.%d"),
        "generated_at": datetime.now(timezone.utc).isoformat().replace("+00:00", "Z"),
        "git_commit": git_sha,
        "theme_version": theme_version,
        "recommendation": "CLEAN-INSTALL CANDIDATE - not production-ready until 04-VALIDATION/RELEASE-ACCEPTANCE.md is signed",
        "checksums_file": "CHECKSUMS.sha256",
        "notes": "Versions are plugin/theme headers. Secrets are never packaged. Plugin Manager ZIP is slim (no offline-packages). Companion excludes build-src/tests.",
    }
    (PROD / "release-manifest.json").write_text(json.dumps(manifest, indent=2) + "\n", encoding="utf-8")

    # Optional inventory/docs — skip if php missing
    php = shutil.which("php")
    if php:
        inv = ROOT / "scripts" / "production-inventory.php"
        docs = ROOT / "scripts" / "production-docs.php"
        if inv.is_file():
            rc = subprocess.call([php, str(inv), str(VAL)])
            if rc != 0:
                print("WARN: production-inventory.php failed", file=sys.stderr)
        if docs.is_file():
            rc = subprocess.call([php, str(docs)])
            if rc != 0:
                print("WARN: production-docs.php failed", file=sys.stderr)
    else:
        print("WARN: php not available; skipped inventory/docs regeneration")

    print(f"PRODUCTION packages written to {PROD}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
