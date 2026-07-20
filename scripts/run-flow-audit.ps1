# Parse blueprint SVG workflows and audit runtime gaps.
# Usage: powershell -File scripts/run-flow-audit.ps1

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot

Write-Host 'NextGen Flow Audit — SVG to runtime'
Write-Host ('=' * 50)

node (Join-Path $root 'scripts\svg-to-flow-manifest.mjs')
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

node (Join-Path $root 'scripts\audit-flow-gaps.mjs')
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-Host ''
Write-Host 'Artifacts:'
Write-Host '  Manifest: docs/workflows/flow-manifest.json'
Write-Host '  Report:   docs/workflows/FLOW-GAP-REPORT.md'
