#!/usr/bin/env bash
set -euo pipefail
echo "=== publish=8081 ==="
docker ps -a --filter publish=8081 --format '{{.ID}} {{.Names}} {{.Status}} {{.Ports}}' || true
echo "=== HostConfig PortBindings containing 8081 ==="
found=0
for c in $(docker ps -aq); do
  bindings=$(docker inspect --format '{{json .HostConfig.PortBindings}}' "$c" 2>/dev/null || echo '{}')
  if echo "$bindings" | grep -q '8081'; then
    found=1
    docker inspect --format '{{.Name}} status={{.State.Status}} bindings={{json .HostConfig.PortBindings}} published={{json .NetworkSettings.Ports}}' "$c"
  fi
done
[ "$found" = 0 ] && echo "(none)"
echo "=== all containers ==="
docker ps -a --format '{{.Names}} | {{.Status}} | {{.Ports}}'
echo "=== host netstat via /mnt/c ==="
# show who has 8081 from windows side is separate
echo done
