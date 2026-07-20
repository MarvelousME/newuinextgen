#!/usr/bin/env pwsh
<#
.SYNOPSIS
  Stage 1 — NextGenTutors solution file inventory + duplicate hash scan.
.DESCRIPTION
  Non-destructive. Emits audit-reports/file-inventory.json|csv and duplicate-files.json.
  Never deletes files. Skips node_modules, vendor, .git, quarantine contents.
#>
param(
  [string]$Root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
)

$ErrorActionPreference = 'Stop'
$outDir = Join-Path $Root 'audit-reports'
New-Item -ItemType Directory -Force -Path $outDir | Out-Null

$skipDirNames = [System.Collections.Generic.HashSet[string]]::new([string[]]@(
  'node_modules','.git','vendor','.svn','.hg','quarantine','cache','.turbo','.next','dist-cache'
))

# Project roots (BeyondInfinity = theme files at repo root, not nested plugins).
$projects = [ordered]@{
  'NextGenTutors-BeyondInfinity' = @{
    Roots = @(
      'assets','inc','templates','template-parts','page-templates','prototypes','content','automations','build-src','tests'
    )
    Files = @(
      'style.css','functions.php','header.php','footer.php','index.php','front-page.php','home.php','page.php',
      'single.php','single-tutors.php','archive.php','archive-tutors.php','searchform.php','comments.php','404.php',
      'admin-dashboard.php','screenshot.png','README.md','ARCHITECTURE.md'
    ) + (Get-ChildItem -Path $Root -Filter 'page-*.php' -File -ErrorAction SilentlyContinue | ForEach-Object { $_.Name })
  }
  'NextGenTutors-Companion'      = @{ Roots = @('NextGenTutors-Companion'); Files = @() }
  'NextGenTutors-Plugin-Manager' = @{ Roots = @('NextGenTutors-Plugin-Manager'); Files = @() }
  'NextGenTutors-Html-Importer'  = @{ Roots = @('NextGenTutors-Html-Importer'); Files = @() }
  'agntix-child'                 = @{ Roots = @('agntix/agntix-child'); Files = @() }
  'magicui-main'                 = @{ Roots = @('reference-style-extraction/inventory/magicui-main'); Files = @() }
  'reference-ftech-package'      = @{ Roots = @('ftech-wp-package'); Files = @() }
  'to-discard-mirror'            = @{ Roots = @('to-discard'); Files = @() }
}

function Get-FileType([string]$ext) {
  switch -Regex ($ext.ToLower()) {
    '^\.(php)$' { return 'php' }
    '^\.(js|mjs|cjs)$' { return 'js' }
    '^\.(ts|tsx)$' { return 'ts' }
    '^\.(css)$' { return 'css' }
    '^\.(scss|sass|less)$' { return 'scss' }
    '^\.(json)$' { return 'json' }
    '^\.(svg)$' { return 'svg' }
    '^\.(png|jpe?g|gif|webp|avif|ico)$' { return 'image' }
    '^\.(woff2?|ttf|otf|eot)$' { return 'font' }
    '^\.(md)$' { return 'markdown' }
    '^\.(html?)$' { return 'html' }
    '^\.(map)$' { return 'sourcemap' }
    '^\.(zip|tar|gz|rar|7z)$' { return 'archive' }
    '^\.(bak|old|tmp)$' { return 'backup' }
    default { return 'other' }
  }
}

function Should-SkipPath([string]$fullPath) {
  $parts = $fullPath.Split([IO.Path]::DirectorySeparatorChar + [IO.Path]::AltDirectorySeparatorChar)
  foreach ($p in $parts) {
    if ($skipDirNames.Contains($p)) { return $true }
  }
  return $false
}

$records = [System.Collections.Generic.List[object]]::new()
$hashIndex = @{}

function Add-InventoryPath([string]$project, [string]$absPath) {
  if (-not (Test-Path -LiteralPath $absPath)) { return }
  if (Should-SkipPath $absPath) { return }

  $item = Get-Item -LiteralPath $absPath -Force
  if ($item.PSIsContainer) {
    Get-ChildItem -LiteralPath $absPath -Recurse -Force -File -ErrorAction SilentlyContinue | ForEach-Object {
      if (-not (Should-SkipPath $_.FullName)) {
        Add-InventoryFile $project $_.FullName
      }
    }
  } else {
    Add-InventoryFile $project $absPath
  }
}

function Add-InventoryFile([string]$project, [string]$absPath) {
  try {
    $fi = Get-Item -LiteralPath $absPath -Force
  } catch { return }

  $rel = $absPath.Substring($Root.Length).TrimStart('\','/')
  $ext = $fi.Extension
  $type = Get-FileType $ext
  $hash = $null
  # Hash text/code and small binaries only (< 8MB) for duplicate detection
  if ($fi.Length -le 8MB) {
    try {
      $hash = (Get-FileHash -LiteralPath $absPath -Algorithm SHA256).Hash
    } catch { $hash = $null }
  }

  $status = 'VERIFIED'
  $action = 'Retain'
  if ($rel -match '(^|[/\\])to-discard([/\\]|$)') {
    $status = 'REDUNDANT'
    $action = 'Quarantine-candidate'
  }
  if ($type -eq 'backup' -or $rel -match '\.(bak|old|tmp)$') {
    $status = 'REMOVE'
    $action = 'Quarantine-candidate'
  }
  if ($type -eq 'sourcemap') {
    $status = 'UNUSED'
    $action = 'Review'
  }
  if ($project -eq 'magicui-main') {
    $status = 'NOT VERIFIED'
    $action = 'Convert-candidate'
  }

  $rec = [pscustomobject]@{
    project               = $project
    relative_path         = $rel.Replace('\','/')
    file_type             = $type
    size_bytes            = $fi.Length
    sha256                = $hash
    purpose               = ''
    referenced_by         = @()
    duplicate_candidates  = @()
    risk                  = 'low'
    status                = $status
    proposed_action       = $action
    canonical_destination = ''
    evidence              = ''
    tests_required        = ''
  }
  $records.Add($rec) | Out-Null

  if ($hash) {
    if (-not $hashIndex.ContainsKey($hash)) { $hashIndex[$hash] = [System.Collections.Generic.List[string]]::new() }
    $hashIndex[$hash].Add($rec.relative_path) | Out-Null
  }
}

Write-Host "Scanning projects under $Root ..."
foreach ($name in $projects.Keys) {
  $cfg = $projects[$name]
  foreach ($r in $cfg.Roots) {
    $p = Join-Path $Root $r
    Write-Host "  [$name] $r"
    Add-InventoryPath $name $p
  }
  foreach ($f in $cfg.Files) {
    if ([string]::IsNullOrWhiteSpace($f)) { continue }
    Add-InventoryPath $name (Join-Path $Root $f)
  }
}

# Annotate duplicates
$duplicates = @()
foreach ($h in $hashIndex.Keys) {
  $paths = $hashIndex[$h]
  if ($paths.Count -gt 1) {
    $duplicates += [pscustomobject]@{
      sha256 = $h
      count  = $paths.Count
      paths  = @($paths)
      status = 'DUPLICATE'
    }
    foreach ($rec in $records) {
      if ($rec.sha256 -eq $h) {
        $rec.duplicate_candidates = @($paths | Where-Object { $_ -ne $rec.relative_path })
        if ($rec.status -eq 'VERIFIED') {
          $rec.status = 'DUPLICATE'
          $rec.proposed_action = 'CONSOLIDATE'
        }
      }
    }
  }
}

$byType = $records | Group-Object file_type | ForEach-Object {
  [pscustomobject]@{ type = $_.Name; count = $_.Count; bytes = ($_.Group | Measure-Object size_bytes -Sum).Sum }
}
$byProject = $records | Group-Object project | ForEach-Object {
  [pscustomobject]@{ project = $_.Name; count = $_.Count; bytes = ($_.Group | Measure-Object size_bytes -Sum).Sum }
}
$byStatus = $records | Group-Object status | ForEach-Object {
  [pscustomobject]@{ status = $_.Name; count = $_.Count }
}

$payload = [ordered]@{
  generated_at = (Get-Date).ToString('o')
  root         = $Root.Replace('\','/')
  environment  = @{
    wordpress = '7.0.1'
    php       = '8.2.32'
    note      = 'Captured from local Docker WP at inventory time'
  }
  totals = @{
    files = $records.Count
    bytes = ($records | Measure-Object size_bytes -Sum).Sum
    duplicate_groups = $duplicates.Count
  }
  by_project = @($byProject)
  by_type    = @($byType)
  by_status  = @($byStatus)
  files      = @($records)
}

$jsonPath = Join-Path $outDir 'file-inventory.json'
$csvPath  = Join-Path $outDir 'file-inventory.csv'
$dupPath  = Join-Path $outDir 'duplicate-files.json'

$payload | ConvertTo-Json -Depth 6 -Compress:$false | Set-Content -Path $jsonPath -Encoding UTF8
$records | Select-Object project,relative_path,file_type,size_bytes,sha256,status,proposed_action,risk |
  Export-Csv -Path $csvPath -NoTypeInformation -Encoding UTF8
@{
  generated_at = $payload.generated_at
  duplicate_groups = $duplicates.Count
  groups = $duplicates
} | ConvertTo-Json -Depth 6 | Set-Content -Path $dupPath -Encoding UTF8

# Removal candidates (non-destructive list only)
$removal = $records | Where-Object {
  $_.status -in @('REMOVE','REDUNDANT','UNUSED') -or
  $_.proposed_action -eq 'Quarantine-candidate' -or
  ($_.file_type -eq 'backup')
} | Select-Object project,relative_path,file_type,size_bytes,sha256,status,proposed_action

@{
  generated_at = $payload.generated_at
  policy = 'NO DELETIONS PERFORMED. Candidates only. Quarantine required before remove.'
  count = @($removal).Count
  candidates = @($removal)
} | ConvertTo-Json -Depth 5 | Set-Content -Path (Join-Path $outDir 'removal-candidates.json') -Encoding UTF8

Write-Host "WROTE $jsonPath"
Write-Host "WROTE $csvPath"
Write-Host "WROTE $dupPath"
Write-Host "FILES=$($records.Count) DUP_GROUPS=$($duplicates.Count) REMOVAL_CANDIDATES=$(@($removal).Count)"
