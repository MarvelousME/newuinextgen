#!/usr/bin/env python3
"""Generate SVG diagrams for workflows, triggers, and user journeys."""

from __future__ import annotations

import re
import textwrap
from pathlib import Path

ROOT = Path(__file__).resolve().parent
WORKFLOWS_DIR = ROOT.parent / "workflows"
OUT_WORKFLOWS = ROOT / "workflows"
OUT_TRIGGERS = ROOT / "triggers"
OUT_JOURNEYS = ROOT / "journeys"

COLORS = {
    "primary": "#0066CC",
    "primary_light": "#E8F1FB",
    "secondary": "#00C896",
    "text": "#1A202C",
    "muted": "#4A5568",
    "border": "#E1E8ED",
    "verified": ("#D1FAE5", "#10B981"),
    "partial": ("#FEF3C7", "#F59E0B"),
    "not_verified": ("#FEE2E2", "#EF4444"),
    "node": ("#FFFFFF", "#0066CC"),
    "external": ("#F5F7FA", "#718096"),
}


def esc(s: str) -> str:
    return (
        s.replace("&", "&amp;")
        .replace("<", "&lt;")
        .replace(">", "&gt;")
        .replace('"', "&quot;")
    )


def evidence_style(ev: str) -> tuple[str, str]:
    ev = ev.upper()
    if "NOT VERIFIED" in ev:
        return COLORS["not_verified"]
    if "PARTIAL" in ev and "VERIFIED" in ev:
        return COLORS["partial"]
    if "PARTIAL" in ev:
        return COLORS["partial"]
    if "VERIFIED" in ev:
        return COLORS["verified"]
    return COLORS["external"]


def wrap(text: str, width: int = 28) -> list[str]:
    text = (text or "—").strip()
    if text in ("—", "None", "none"):
        return ["—"]
    parts = re.split(r"\s*->\s*|\s*→\s*|\s*,\s*", text)
    lines: list[str] = []
    for part in parts:
        lines.extend(textwrap.wrap(part.strip(), width=width) or [part.strip()])
    return lines[:6] or ["—"]


def parse_workflow_md(path: Path) -> dict:
    text = path.read_text(encoding="utf-8")
    title_m = re.search(r"^# WF-\d+:\s*(.+)$", text, re.M)
    ev_m = re.search(r"\*\*Evidence:\*\*\s*(.+)$", text, re.M)
    fields: dict[str, str] = {}
    for m in re.finditer(r"\|\s\*\*(\w+)\*\*\s\|\s(.+?)\s\|", text):
        fields[m.group(1).lower()] = m.group(2).strip()
    wf_id_m = re.match(r"(WF-\d+)", path.stem)
    wf_id = wf_id_m.group(1) if wf_id_m else path.stem
    return {
        "id": wf_id,
        "slug": path.stem,
        "name": title_m.group(1).strip() if title_m else path.stem,
        "evidence": ev_m.group(1).strip() if ev_m else "UNKNOWN",
        "fields": fields,
    }


def arrow(x1: int, y1: int, x2: int, y2: int, dashed: bool = False) -> str:
    dash = ' stroke-dasharray="6 4"' if dashed else ""
    return (
        f'<line x1="{x1}" y1="{y1}" x2="{x2}" y2="{y2}" stroke="{COLORS["muted"]}" '
        f'stroke-width="2" marker-end="url(#arrow)"{dash}/>'
    )


def box(x: int, y: int, w: int, h: int, lines: list[str], fill: str, stroke: str, rx: int = 8) -> str:
    lh = 16
    ty = y + 22
    text = "".join(
        f'<tspan x="{x + w // 2}" dy="{lh if i else 0}">{esc(line)}</tspan>'
        for i, line in enumerate(lines[:4])
    )
    return f"""
    <rect x="{x}" y="{y}" width="{w}" height="{h}" rx="{rx}" fill="{fill}" stroke="{stroke}" stroke-width="1.5"/>
    <text x="{x + w // 2}" y="{ty}" text-anchor="middle" font-family="Inter,Segoe UI,sans-serif" font-size="12" fill="{COLORS['text']}">{text}</text>
    """


def diamond(cx: int, cy: int, size: int, label: str, fill: str, stroke: str) -> str:
    pts = f"{cx},{cy - size} {cx + size},{cy} {cx},{cy + size} {cx - size},{cy}"
    return f"""
    <polygon points="{pts}" fill="{fill}" stroke="{stroke}" stroke-width="1.5"/>
    <text x="{cx}" y="{cy + 4}" text-anchor="middle" font-family="Inter,Segoe UI,sans-serif" font-size="11" fill="{COLORS['text']}">{esc(label[:18])}</text>
    """


def header(title: str, subtitle: str, ev: str, width: int) -> tuple[str, int]:
    fill, stroke = evidence_style(ev)
    y = 16
    svg = f"""
    <text x="24" y="{y + 20}" font-family="Sora,Segoe UI,sans-serif" font-size="20" font-weight="600" fill="{COLORS['text']}">{esc(title)}</text>
    <text x="24" y="{y + 42}" font-family="Inter,Segoe UI,sans-serif" font-size="12" fill="{COLORS['muted']}">{esc(subtitle)}</text>
    <rect x="{width - 170}" y="{y + 4}" width="146" height="28" rx="14" fill="{fill}" stroke="{stroke}" stroke-width="1.5"/>
    <text x="{width - 97}" y="{y + 23}" text-anchor="middle" font-family="Inter,Segoe UI,sans-serif" font-size="11" font-weight="600" fill="{COLORS['text']}">{esc(ev)}</text>
    """
    return svg, y + 64


def workflow_svg(wf: dict) -> str:
    f = wf["fields"]
    ev = wf["evidence"]
    not_impl = "NOT VERIFIED" in ev.upper() and f.get("trigger", "None") in ("None", "—", "")
    width = 920
    height = 420 if not_impl else 480

    parts: list[str] = []

    if not_impl:
        hdr, top = header(f"{wf['id']}: {wf['name']}", "BPMN flow · BeyondInfinity v1.4.6", ev, width)
        parts.append(hdr)
        fill, stroke = COLORS["not_verified"]
        parts.append(
            box(120, top + 40, width - 240, 120, ["Not implemented in theme runtime", "No trigger or handler found"], fill, stroke, rx=12)
        )
        parts.append(
            f'<text x="{width // 2}" y="{top + 200}" text-anchor="middle" font-family="Inter,sans-serif" font-size="13" fill="{COLORS["muted"]}">Planned workflow — companion or future phase required</text>'
        )
    else:
        steps_raw = f.get("steps", "—")
        step_chunks = [s.strip() for s in re.split(r"\s*->\s*|\s*→\s*", steps_raw) if s.strip() and s.strip() != "—"]
        if not step_chunks:
            step_chunks = wrap(steps_raw, 24)

        nodes = [("Trigger", wrap(f.get("trigger", "—"), 22)[0])]
        for s in step_chunks[:4]:
            nodes.append(("Step", s[:32]))
        nodes.append(("Output", wrap(f.get("outputs", "—"), 22)[0]))

        nfill, nstroke = COLORS["node"]
        bw, bh, gap = 150, 64, 24
        num_nodes = len(nodes)
        width = max(920, 48 + num_nodes * (bw + gap) + 40)
        height = max(height, 480)
        hdr, top = header(f"{wf['id']}: {wf['name']}", "BPMN flow · BeyondInfinity v1.4.6", ev, width)
        parts.append(hdr)

        x, y = 40, top + 30
        coords: list[tuple[int, int]] = []
        for i, (kind, label) in enumerate(nodes):
            cx = x + i * (bw + gap)
            fill = COLORS["primary_light"] if kind == "Trigger" else nfill
            stroke = COLORS["primary"] if kind in ("Trigger", "Output") else nstroke
            parts.append(box(cx, y, bw, bh, [label], fill, stroke))
            coords.append((cx + bw, y + bh // 2))

        for i in range(len(coords) - 1):
            x1 = coords[i][0]
            x2 = x + (i + 1) * (bw + gap)
            parts.append(arrow(x1 + 4, y + bh // 2, x2 - 4, y + bh // 2))

        decision = f.get("decisions", "—")
        if decision and decision not in ("—", "None"):
            dfill, dstroke = COLORS["partial"]
            parts.append(diamond(40 + (bw + gap) * 2, y + bh + 55, 42, decision[:16], dfill, dstroke))
            parts.append(arrow(40 + (bw + gap) * 2, y + bh, 40 + (bw + gap) * 2, y + bh + 13))
            exc = wrap(f.get("exceptions", "—"), 20)
            parts.append(box(40 + (bw + gap) * 2 - 75, y + bh + 110, 150, 56, exc[:2], COLORS["not_verified"][0], COLORS["not_verified"][1]))
            parts.append(arrow(40 + (bw + gap) * 2 + 42, y + bh + 55, 40 + (bw + gap) * 2 + 75, y + bh + 110, dashed=True))

        meta_y = y + 200
        meta = [
            ("Actor", f.get("actor", "—")),
            ("Notify", f.get("notifications", "—")),
            ("DB", f.get("db changes", "—")),
            ("Audit", f.get("audit", "—")),
        ]
        mw = (width - 80) // 2 - 8
        for i, (k, v) in enumerate(meta):
            mx = 40 + (i % 2) * (mw + 16)
            my = meta_y + (i // 2) * 72
            parts.append(box(mx, my, mw, 60, [k, *wrap(v, 34)[:2]], COLORS["external"][0], COLORS["external"][1]))

    return f"""<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="{width}" height="{height}" viewBox="0 0 {width} {height}" role="img" aria-label="{esc(wf['name'])} workflow diagram">
  <defs>
    <marker id="arrow" markerWidth="8" markerHeight="8" refX="7" refY="4" orient="auto">
      <path d="M0,0 L8,4 L0,8 z" fill="{COLORS['muted']}"/>
    </marker>
  </defs>
  <rect width="100%" height="100%" fill="#FAFBFC"/>
  {''.join(parts)}
</svg>
"""


TRIGGERS = [
    ("USER_REGISTERED", "wp.user_registered", "user_register hook", "VERIFIED"),
    ("TUTOR_SUBMITTED", "ngt.tutor_application.submitted", "become_tutor form", "VERIFIED"),
    ("TUTOR_APPROVED", "ngt.tutor.approved", "Admin Operations", "VERIFIED"),
    ("TUTOR_REJECTED", "—", "—", "NOT VERIFIED"),
    ("STUDENT_CREATED", "ngt.student_register.submitted", "student_register form", "VERIFIED"),
    ("PARENT_CREATED", "ngt.parent_register.submitted", "parent_register form", "VERIFIED"),
    ("MATCH_REQUESTED", "ngt.find_tutor.submitted", "find_tutor form", "VERIFIED"),
    ("MATCH_ACCEPTED", "—", "—", "NOT VERIFIED"),
    ("BOOKING_CREATED", "amelia.booking.created", "Amelia plugin", "PARTIAL"),
    ("BOOKING_CONFIRMED", "—", "—", "NOT VERIFIED"),
    ("PAYMENT_RECEIVED", "woocommerce.order.completed", "WooCommerce hook", "PARTIAL"),
    ("PAYMENT_FAILED", "—", "—", "NOT VERIFIED"),
    ("LESSON_STARTED", "—", "—", "NOT VERIFIED"),
    ("LESSON_COMPLETED", "ngt.lesson.completed", "External LMS", "PARTIAL"),
    ("INVOICE_GENERATED", "—", "—", "NOT VERIFIED"),
    ("PAYOUT_GENERATED", "—", "—", "NOT VERIFIED"),
    ("REVIEW_SUBMITTED", "—", "—", "NOT VERIFIED"),
    ("SUPPORT_CREATED", "ngt.support.escalated", "contact_support form", "VERIFIED"),
    ("DAILY_HEALTH_CHECK", "ngt.daily.health_check", "External cron", "PARTIAL"),
]


def trigger_catalog_svg() -> str:
    width, row_h, start_y = 960, 36, 100
    height = start_y + len(TRIGGERS) * row_h + 40
    parts = []
    hdr, _ = header("Trigger Matrix Catalog", "All logical triggers · Appendix B", "v1.4.6", width)
    parts.append(hdr)
    cols = [("Logical", 160), ("Event key", 280), ("Source", 220), ("Status", 120)]
    x = 24
    for label, w in cols:
        parts.append(
            f'<rect x="{x}" y="{start_y - 28}" width="{w}" height="24" fill="{COLORS["primary_light"]}" stroke="{COLORS["border"]}"/>'
        )
        parts.append(
            f'<text x="{x + 8}" y="{start_y - 10}" font-family="Inter,sans-serif" font-size="11" font-weight="600" fill="{COLORS["text"]}">{esc(label)}</text>'
        )
        x += w + 4
    for i, (logical, event, source, status) in enumerate(TRIGGERS):
        y = start_y + i * row_h
        fill, stroke = evidence_style(status)
        x = 24
        for val, w in zip((logical, event, source, status), (c[1] for c in cols)):
            parts.append(f'<rect x="{x}" y="{y}" width="{w}" height="{row_h - 4}" fill="#FFFFFF" stroke="{COLORS["border"]}"/>')
            if val == status:
                parts.append(
                    f'<rect x="{x + 6}" y="{y + 8}" width="{w - 12}" height="18" rx="9" fill="{fill}" stroke="{stroke}"/>'
                )
                parts.append(
                    f'<text x="{x + w // 2}" y="{y + 21}" text-anchor="middle" font-family="Inter,sans-serif" font-size="10" font-weight="600">{esc(status)}</text>'
                )
            else:
                parts.append(
                    f'<text x="{x + 8}" y="{y + 22}" font-family="Inter,sans-serif" font-size="11" fill="{COLORS["text"]}">{esc(val[:36])}</text>'
                )
            x += w + 4
    return f"""<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="{width}" height="{height}" viewBox="0 0 {width} {height}" role="img" aria-label="Trigger matrix catalog">
  <rect width="100%" height="100%" fill="#FAFBFC"/>
  {''.join(parts)}
</svg>
"""


def trigger_flow_svg(logical: str, event: str, source: str, status: str) -> str:
    width, height = 880, 360
    fill, stroke = evidence_style(status)
    not_impl = status == "NOT VERIFIED"
    parts = []
    hdr, top = header(logical.replace("_", " ").title(), event if event != "—" else "No event key", status, width)
    parts.append(hdr)

    if not_impl:
        parts.append(box(140, top + 50, width - 280, 100, ["No dispatcher", "Not implemented"], fill, stroke, rx=12))
    else:
        chain = [
            ("Source", source),
            ("Dispatch", "bi_workflow_dispatch()"),
            ("Actions", "log · RTM · mail · role"),
            ("Queues", "bi_rtm_queue / ngc_form_queue"),
        ]
        x, y, bw, bh, gap = 48, top + 40, 170, 70, 28
        for i, (k, v) in enumerate(chain):
            cx = x + i * (bw + gap)
            parts.append(box(cx, y, bw, bh, [k, v[:24]], COLORS["primary_light"] if i == 0 else COLORS["node"][0], COLORS["primary"]))
            if i < len(chain) - 1:
                parts.append(arrow(cx + bw, y + bh // 2, cx + bw + gap, y + bh // 2))
        parts.append(
            f'<text x="{width // 2}" y="{y + 130}" text-anchor="middle" font-family="Inter,sans-serif" font-size="12" fill="{COLORS["muted"]}">Audit: bi_workflow_log · Notify: email / RTM / OpenWA</text>'
        )

    slug = logical.lower()
    return f"""<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="{width}" height="{height}" viewBox="0 0 {width} {height}" role="img" aria-label="{esc(logical)} trigger flow">
  <defs><marker id="arrow" markerWidth="8" markerHeight="8" refX="7" refY="4" orient="auto"><path d="M0,0 L8,4 L0,8 z" fill="{COLORS['muted']}"/></marker></defs>
  <rect width="100%" height="100%" fill="#FAFBFC"/>
  {''.join(parts)}
</svg>
""", slug


JOURNEYS = {
    "visitor": {
        "title": "Visitor Journey",
        "evidence": "VERIFIED",
        "steps": [
            ("Discover", "Browse site", "VERIFIED"),
            ("Compare", "Subjects / tutors", "VERIFIED"),
            ("Submit lead", "Find tutor / contact", "VERIFIED"),
            ("Thank you", "Confirmation UX", "VERIFIED"),
        ],
    },
    "parent": {
        "title": "Parent Journey",
        "evidence": "PARTIAL",
        "steps": [
            ("Awareness", "Visit site", "VERIFIED"),
            ("Search", "Find a tutor", "VERIFIED"),
            ("Request", "Submit intake", "VERIFIED"),
            ("Notify", "Workflow dispatch", "VERIFIED"),
            ("Match", "Staff matching", "PARTIAL"),
            ("Book", "Amelia booking", "PARTIAL"),
            ("Pay", "WooCommerce", "PARTIAL"),
            ("Track", "Parent dashboard", "PARTIAL"),
            ("Review", "Ratings display", "PARTIAL"),
        ],
    },
    "student": {
        "title": "Student Journey",
        "evidence": "PARTIAL",
        "steps": [
            ("Register", "Self-registration form", "VERIFIED"),
            ("Login", "Role redirect", "VERIFIED"),
            ("Dashboard", "REST shell", "PARTIAL"),
            ("Sessions", "Attendance / homework", "NOT VERIFIED"),
            ("Progress", "LMS data", "NOT VERIFIED"),
        ],
    },
    "tutor-applicant": {
        "title": "Tutor Applicant Journey",
        "evidence": "PARTIAL",
        "steps": [
            ("Discover", "Become a tutor page", "VERIFIED"),
            ("Apply", "Form submit", "VERIFIED"),
            ("Queue", "Staff notification", "VERIFIED"),
            ("Review", "Manual vetting", "PARTIAL"),
            ("Approve", "Operations screen", "VERIFIED"),
            ("Onboard", "Profile / availability", "PARTIAL"),
        ],
    },
    "approved-tutor": {
        "title": "Approved Tutor Journey",
        "evidence": "PARTIAL",
        "steps": [
            ("Login", "Tutor dashboard", "VERIFIED"),
            ("Notify", "RTM / email", "VERIFIED"),
            ("Accept", "Booking confirm", "PARTIAL"),
            ("Deliver", "Lesson complete", "PARTIAL"),
            ("Payout", "Earnings", "NOT VERIFIED"),
        ],
    },
    "admin": {
        "title": "Admin Journey",
        "evidence": "PARTIAL",
        "steps": [
            ("Login", "Admin dashboard", "VERIFIED"),
            ("Sync", "Launch pages", "VERIFIED"),
            ("Ops", "Queues / approvals", "VERIFIED"),
            ("OpenWA", "WhatsApp status", "VERIFIED"),
            ("Report", "Analytics KPIs", "NOT VERIFIED"),
        ],
    },
}


def journey_svg(key: str, data: dict) -> str:
    steps = data["steps"]
    width = max(920, 80 + len(steps) * 108)
    height = 320
    parts = []
    hdr, top = header(data["title"], "User journey map · Phase 3", data["evidence"], width)
    parts.append(hdr)

    lane_y = top + 30
    parts.append(
        f'<line x1="40" y1="{lane_y + 60}" x2="{width - 40}" y2="{lane_y + 60}" stroke="{COLORS["border"]}" stroke-width="2"/>'
    )

    for i, (phase, desc, ev) in enumerate(steps):
        cx = 56 + i * 104
        fill, stroke = evidence_style(ev)
        parts.append(
            f'<circle cx="{cx}" cy="{lane_y + 60}" r="10" fill="{fill}" stroke="{stroke}" stroke-width="2"/>'
        )
        if i < len(steps) - 1:
            parts.append(arrow(cx + 12, lane_y + 60, cx + 92, lane_y + 60))
        parts.append(
            f'<text x="{cx}" y="{lane_y + 28}" text-anchor="middle" font-family="Inter,sans-serif" font-size="11" font-weight="600" fill="{COLORS["text"]}">{esc(phase)}</text>'
        )
        parts.append(
            f'<text x="{cx}" y="{lane_y + 92}" text-anchor="middle" font-family="Inter,sans-serif" font-size="10" fill="{COLORS["muted"]}">{esc(desc[:16])}</text>'
        )
        parts.append(
            f'<rect x="{cx - 38}" y="{lane_y + 104}" width="76" height="18" rx="9" fill="{fill}" stroke="{stroke}"/>'
        )
        parts.append(
            f'<text x="{cx}" y="{lane_y + 117}" text-anchor="middle" font-family="Inter,sans-serif" font-size="9" font-weight="600">{esc(ev)}</text>'
        )

    return f"""<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="{width}" height="{height}" viewBox="0 0 {width} {height}" role="img" aria-label="{esc(data['title'])}">
  <defs><marker id="arrow" markerWidth="8" markerHeight="8" refX="7" refY="4" orient="auto"><path d="M0,0 L8,4 L0,8 z" fill="{COLORS['muted']}"/></marker></defs>
  <rect width="100%" height="100%" fill="#FAFBFC"/>
  {''.join(parts)}
</svg>
"""


def main() -> None:
    OUT_WORKFLOWS.mkdir(parents=True, exist_ok=True)
    OUT_TRIGGERS.mkdir(parents=True, exist_ok=True)
    OUT_JOURNEYS.mkdir(parents=True, exist_ok=True)

    wf_count = 0
    for md in sorted(WORKFLOWS_DIR.glob("WF-*.md")):
        wf = parse_workflow_md(md)
        out = OUT_WORKFLOWS / f"{md.stem}.svg"
        out.write_text(workflow_svg(wf), encoding="utf-8")
        wf_count += 1

    (OUT_TRIGGERS / "trigger-matrix-catalog.svg").write_text(trigger_catalog_svg(), encoding="utf-8")
    tr_count = 1
    for logical, event, source, status in TRIGGERS:
        svg, slug = trigger_flow_svg(logical, event, source, status)
        (OUT_TRIGGERS / f"trigger-{slug}.svg").write_text(svg, encoding="utf-8")
        tr_count += 1

    j_count = 0
    for key, data in JOURNEYS.items():
        (OUT_JOURNEYS / f"journey-{key}.svg").write_text(journey_svg(key, data), encoding="utf-8")
        j_count += 1

    readme = f"""# Enterprise Blueprint — SVG Diagrams

Generated by `generate-diagrams.py` from workflow specs and Appendix B.

| Category | Count | Path |
|----------|-------|------|
| Workflows | {wf_count} | [workflows/](workflows/) |
| Triggers | {tr_count} | [triggers/](triggers/) |
| User journeys | {j_count} | [journeys/](journeys/) |

## Regenerate

```bash
python docs/enterprise-blueprint/diagrams/generate-diagrams.py
```

## Evidence colors

- Green — VERIFIED
- Amber — PARTIAL
- Red — NOT VERIFIED
"""
    (ROOT / "README.md").write_text(readme, encoding="utf-8")
    print(f"Generated {wf_count} workflow, {tr_count} trigger, {j_count} journey SVGs")


if __name__ == "__main__":
    main()
