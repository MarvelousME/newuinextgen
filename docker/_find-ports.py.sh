#!/usr/bin/env bash
set -euo pipefail
cd /mnt/c/Users/marvi/Downloads/wetransfer_newuinextgen_2026-07-07_1929/newuinextgen/docker

echo "=== Who owns 8081/8082/8787 in Docker? ==="
docker ps -a --format '{{.ID}} {{.Names}} {{.Ports}}' 
python3 - <<'PY'
import json, subprocess
out = subprocess.check_output(["docker", "ps", "-aq"], text=True).split()
for cid in out:
    try:
        raw = subprocess.check_output(["docker", "inspect", cid], text=True)
        data = json.loads(raw)[0]
        name = data.get("Name","")
        ports = data.get("NetworkSettings",{}).get("Ports") or {}
        hostcfg = (data.get("HostConfig") or {}).get("PortBindings") or {}
        interesting = []
        for k,v in {**ports, **{kk:vv for kk,vv in hostcfg.items()}}.items():
            s = str(v)
            if any(p in s or p in k for p in ("8081","8082","8787","8890")):
                interesting.append(f"{k}->{v}")
        if interesting:
            print(name, " ".join(interesting), "state=", data.get("State",{}).get("Status"))
    except Exception as e:
        print(cid, e)
PY
