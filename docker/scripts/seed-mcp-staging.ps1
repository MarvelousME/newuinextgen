# Seed NGC MCP registry from config/mcp-staging-servers.json (staging :8890).
# Usage (from docker/):  .\scripts\seed-mcp-staging.ps1

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot

Write-Host "Seeding MCP staging servers into WordPress (project newuinextgen)..."
docker compose -f (Join-Path $root "docker-compose.yml") exec -T wordpress php /var/www/config/seed-mcp-staging.php
if ($LASTEXITCODE -ne 0) { throw "seed failed exit=$LASTEXITCODE" }
Write-Host "Done. Admin: WP Admin -> NEXT GEN TUTORS -> Agentic -> MCP Servers"
