# Build first-party release ZIPs one package at a time (disk-safe).
# Usage: powershell -ExecutionPolicy Bypass -File scripts/build-release-lean.ps1

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$out = Join-Path $root 'delivery\installable-packages'
$stageRoot = Join-Path $env:TEMP ('ngt-rel-' + [guid]::NewGuid().ToString('N').Substring(0,8))
New-Item -ItemType Directory -Force -Path $out | Out-Null
New-Item -ItemType Directory -Force -Path $stageRoot | Out-Null

$packages = @(
  @{ Name = 'NextGenTutors-BeyondInfinity'; Src = 'NextGenTutors-BeyondInfinity'; Kind = 'theme'; Version = '1.9.17' },
  @{ Name = 'NextGenTutors-Companion'; Src = 'NextGenTutors-Companion'; Kind = 'plugin'; Version = '1.9.5' },
  @{ Name = 'NextGenTutors-AI-Integration'; Src = 'NextGenTutors-AI-Integration'; Kind = 'plugin'; Version = '1.9.5' },
  @{ Name = 'NextGenTutors-Mission-Control'; Src = 'NextGenTutors-Mission-Control'; Kind = 'plugin'; Version = '1.9.5' },
  @{ Name = 'NextGenTutors-Plugin-Manager'; Src = 'NextGenTutors-Plugin-Manager'; Kind = 'plugin'; Version = '1.9.5' },
  @{ Name = 'NextGenTutors-Html-Importer'; Src = 'NextGenTutors-Html-Importer'; Kind = 'plugin'; Version = '1.9.5' },
  @{ Name = 'ngt-ui-library'; Src = 'ui-library'; Kind = 'library'; Version = '1.9.5' }
)

$excludeXd = @('.git','node_modules','vendor','build-src','tests','.cursor','audit-reports','_extracted')
$checksums = @()
$manifestPkgs = @()

function Get-FreeGB {
  [math]::Round((Get-PSDrive C).Free / 1GB, 2)
}

Write-Host "Preflight free disk: $(Get-FreeGB) GB"
if ((Get-FreeGB) -lt 1.0) { throw "Insufficient free disk (<1GB) for packaging preflight." }

foreach ($p in $packages) {
  $src = Join-Path $root $p.Src
  if (-not (Test-Path $src)) { throw "Missing $($p.Src)" }
  $zipName = "$($p.Name)-v$($p.Version).zip"
  $zipPath = Join-Path $out $zipName
  $stage = Join-Path $stageRoot $p.Name
  if (Test-Path $stage) { Remove-Item -Recurse -Force $stage }
  New-Item -ItemType Directory -Path $stage | Out-Null

  $xdArgs = @()
  foreach ($d in $excludeXd) { $xdArgs += @('/XD', $d) }
  & robocopy $src $stage /E /NFL /NDL /NJH /NJS /NC /NS @xdArgs /XF *.zip *.map | Out-Null
  if ($LASTEXITCODE -ge 8) { throw "robocopy failed for $($p.Name)" }

  if (Test-Path $zipPath) { Remove-Item -Force $zipPath }
  Compress-Archive -Path $stage -DestinationPath $zipPath -Force
  Remove-Item -Recurse -Force $stage

  $hash = (Get-FileHash -Algorithm SHA256 -Path $zipPath).Hash.ToLower()
  $checksums += "$hash  $zipName"
  $manifestPkgs += @{
    name = $p.Name
    version = $p.Version
    path = "installable-packages/$zipName"
    sha256 = $hash
    bytes = (Get-Item $zipPath).Length
  }
  Write-Host "Built $zipName ($([math]::Round((Get-Item $zipPath).Length/1MB,2)) MB) free=$(Get-FreeGB)GB"
}

$checksumFile = Join-Path $out 'checksums.sha256'
Set-Content -Path $checksumFile -Value ($checksums -join "`n") -Encoding ASCII

# Agent gateway bundle (no node_modules)
$gwSrc = Join-Path $root 'services\ngt-agent-gateway'
$gwOutDir = Join-Path $root 'delivery\agent-gateway'
New-Item -ItemType Directory -Force -Path $gwOutDir | Out-Null
$gwStage = Join-Path $stageRoot 'ngt-agent-gateway'
New-Item -ItemType Directory -Force -Path $gwStage | Out-Null
robocopy $gwSrc $gwStage /E /NFL /NDL /NJH /NJS /NC /NS /XD node_modules .git tests /XF *.zip | Out-Null
$gwZip = Join-Path $gwOutDir 'ngt-agent-gateway-v1.0.0.zip'
if (Test-Path $gwZip) { Remove-Item $gwZip -Force }
Compress-Archive -Path $gwStage -DestinationPath $gwZip -Force
$gwHash = (Get-FileHash -Algorithm SHA256 -Path $gwZip).Hash.ToLower()
Add-Content -Path $checksumFile -Value "`n$gwHash  ../agent-gateway/ngt-agent-gateway-v1.0.0.zip" -Encoding ASCII

$manifest = @{
  schema_version = '1.1.0'
  generated_at = (Get-Date).ToUniversalTime().ToString('o')
  git_commit = (git -C $root rev-parse HEAD)
  recommendation = 'STAGING ONLY'
  packages = $manifestPkgs
  agent_gateway = @{ path = 'agent-gateway/ngt-agent-gateway-v1.0.0.zip'; sha256 = $gwHash; version = '1.0.0' }
  checksums_file = 'installable-packages/checksums.sha256'
  notes = 'Lean disk-safe packaging. Secrets never packaged. Full Docker install verification required separately.'
}
$manifest | ConvertTo-Json -Depth 6 | Set-Content (Join-Path $root 'delivery\release-manifest.json') -Encoding UTF8

Remove-Item -Recurse -Force $stageRoot -ErrorAction SilentlyContinue
Write-Host "DONE free=$(Get-FreeGB)GB"
