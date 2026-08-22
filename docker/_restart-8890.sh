#!/usr/bin/env bash
set -euo pipefail
cd /mnt/c/Users/marvi/Downloads/wetransfer_newuinextgen_2026-07-07_1929/newuinextgen/docker
docker-compose up -d --no-deps db
for i in $(seq 1 40); do
  st=$(docker inspect -f '{{.State.Health.Status}}' newuinextgen_db_1 2>/dev/null || echo none)
  echo "db=$st"
  if [ "$st" = healthy ]; then break; fi
  sleep 3
done
docker-compose up -d --no-deps wordpress phpmyadmin
sleep 12
docker-compose ps
curl -s -o /dev/null -w 'HTTP:%{http_code}\n' http://127.0.0.1:8890/ || true
