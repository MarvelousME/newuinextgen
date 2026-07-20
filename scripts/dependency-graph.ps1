#!/usr/bin/env pwsh
<#
.SYNOPSIS
  Stage 1 — Seed PHP/JS/CSS dependency graphs (static regex analysis).
.DESCRIPTION
  Non-destructive. Does not resolve dynamic paths. Marks UNSAFE TO REMOVE where dynamic.
#>
param(
  [string]$Root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
)

$ErrorActionPreference = 'Stop'
$outDir = Join-Path $Root 'audit-reports'
New-Item -ItemType Directory -Force -Path $outDir | Out-Null

$scopes = @(
  @{ project = 'NextGenTutors-BeyondInfinity'; paths = @('inc','assets','functions.php','style.css') },
  @{ project = 'NextGenTutors-Companion'; paths = @('NextGenTutors-Companion') },
  @{ project = 'NextGenTutors-Plugin-Manager'; paths = @('NextGenTutors-Plugin-Manager') },
  @{ project = 'NextGenTutors-Html-Importer'; paths = @('NextGenTutors-Html-Importer') },
  @{ project = 'agntix-child'; paths = @('agntix/agntix-child') }
)

$nodes = [System.Collections.Generic.List[object]]::new()
$edges = [System.Collections.Generic.List[object]]::new()
$phpAnalysis = [System.Collections.Generic.List[object]]::new()
$jsAnalysis = [System.Collections.Generic.List[object]]::new()
$cssAnalysis = [System.Collections.Generic.List[object]]::new()

function Rel([string]$abs) {
  return $abs.Substring($Root.Length).TrimStart('\','/').Replace('\','/')
}

function Collect-Files([string]$relRoot, [string[]]$exts) {
  $abs = Join-Path $Root $relRoot
  if (-not (Test-Path $abs)) { return @() }
  if ((Get-Item $abs).PSIsContainer) {
    return Get-ChildItem -LiteralPath $abs -Recurse -File -Force -ErrorAction SilentlyContinue |
      Where-Object {
        $exts -contains $_.Extension.ToLower() -and
        $_.FullName -notmatch '[\\/]node_modules[\\/]' -and
        $_.FullName -notmatch '[\\/]vendor[\\/]'
      }
  }
  if ($exts -contains ([IO.Path]::GetExtension($abs).ToLower())) { return @(Get-Item $abs) }
  return @()
}

foreach ($scope in $scopes) {
  foreach ($p in $scope.paths) {
    foreach ($f in (Collect-Files $p @('.php'))) {
      $text = Get-Content -LiteralPath $f.FullName -Raw -ErrorAction SilentlyContinue
      if ($null -eq $text) { continue }
      $rel = Rel $f.FullName
      $nodes.Add([pscustomobject]@{ id = $rel; project = $scope.project; kind = 'php' }) | Out-Null

      $requires = [regex]::Matches($text, '(?:require|include)(?:_once)?\s*\(?\s*([^;]+)') | ForEach-Object {
        $_.Groups[1].Value.Trim().TrimEnd(')').Trim()
      } | Where-Object { $_ -and $_ -notmatch '^\s*//' } | Select-Object -First 40
      $hooks = [regex]::Matches($text, 'add_(?:action|filter)\s*\(\s*[''"]([^''"]+)[''"]') | ForEach-Object { $_.Groups[1].Value }
      $shortcodes = [regex]::Matches($text, 'add_shortcode\s*\(\s*[''"]([^''"]+)[''"]') | ForEach-Object { $_.Groups[1].Value }
      $rest = [regex]::Matches($text, 'register_rest_route\s*\(\s*[''"]([^''"]+)[''"]\s*,\s*[''"]([^''"]+)[''"]') | ForEach-Object { $_.Groups[1].Value + $_.Groups[2].Value }
      $enqueues = [regex]::Matches($text, 'wp_enqueue_(?:script|style)\s*\(\s*[''"]([^''"]+)[''"]') | ForEach-Object { $_.Groups[1].Value }
      $dynamic = $text -match '\$[a-zA-Z_][\w]*\s*\.\s*[''"].*\.php' -or $text -match 'glob\s*\(' -or $text -match 'file_exists\s*\(\s*\$'

      foreach ($r in $requires) {
        $edges.Add([pscustomobject]@{ from = $rel; to = $r; type = 'php_include'; project = $scope.project }) | Out-Null
      }
      $phpAnalysis.Add([pscustomobject]@{
        file = $rel
        project = $scope.project
        requires = @($requires | Select-Object -Unique)
        hooks = @($hooks | Select-Object -Unique)
        shortcodes = @($shortcodes | Select-Object -Unique)
        rest_routes = @($rest | Select-Object -Unique)
        enqueue_handles = @($enqueues | Select-Object -Unique)
        dynamic_load_risk = [bool]$dynamic
        status = if ($dynamic) { 'UNSAFE TO REMOVE' } else { 'VERIFIED' }
      }) | Out-Null
    }

    foreach ($f in (Collect-Files $p @('.js','.mjs'))) {
      $text = Get-Content -LiteralPath $f.FullName -Raw -ErrorAction SilentlyContinue
      if ($null -eq $text) { continue }
      $rel = Rel $f.FullName
      $nodes.Add([pscustomobject]@{ id = $rel; project = $scope.project; kind = 'js' }) | Out-Null
      $imports = [regex]::Matches($text, '(?:import|from)\s+[''"]([^''"]+)[''"]|require\s*\(\s*[''"]([^''"]+)[''"]\s*\)') |
        ForEach-Object { if ($_.Groups[1].Value) { $_.Groups[1].Value } else { $_.Groups[2].Value } }
      foreach ($i in $imports) {
        $edges.Add([pscustomobject]@{ from = $rel; to = $i; type = 'js_import'; project = $scope.project }) | Out-Null
      }
      $jsAnalysis.Add([pscustomobject]@{
        file = $rel
        project = $scope.project
        imports = @($imports | Select-Object -Unique)
        uses_gsap = [bool]($text -match '\bgsap\b|ScrollTrigger')
        uses_three = [bool]($text -match '\bTHREE\b|from [''"]three[''"]')
        status = 'VERIFIED'
      }) | Out-Null
    }

    foreach ($f in (Collect-Files $p @('.css','.scss'))) {
      $text = Get-Content -LiteralPath $f.FullName -Raw -ErrorAction SilentlyContinue
      if ($null -eq $text) { continue }
      $rel = Rel $f.FullName
      $nodes.Add([pscustomobject]@{ id = $rel; project = $scope.project; kind = 'css' }) | Out-Null
      $imports = [regex]::Matches($text, '@import\s+(?:url\()?[''"]?([^''"\);]+)') | ForEach-Object { $_.Groups[1].Value.Trim() }
      $customProps = [regex]::Matches($text, '--[a-zA-Z0-9-_]+') | ForEach-Object { $_.Value } | Select-Object -Unique
      foreach ($i in $imports) {
        $edges.Add([pscustomobject]@{ from = $rel; to = $i; type = 'css_import'; project = $scope.project }) | Out-Null
      }
      $cssAnalysis.Add([pscustomobject]@{
        file = $rel
        project = $scope.project
        imports = @($imports | Select-Object -Unique)
        token_candidates = @($customProps | Select-Object -First 80)
        status = 'VERIFIED'
      }) | Out-Null
    }
  }
}

$graph = [ordered]@{
  generated_at = (Get-Date).ToString('o')
  method = 'static-regex'
  limitations = @(
    'Dynamic includes/globs not fully resolved',
    'Composer/npm graphs not expanded',
    'DB-stored Elementor/WPBakery template refs not scanned',
    'No runtime enqueue proof — mark those UNSAFE TO REMOVE'
  )
  nodes = @($nodes)
  edges = @($edges)
  stats = @{
    nodes = $nodes.Count
    edges = $edges.Count
    php_files = $phpAnalysis.Count
    js_files = $jsAnalysis.Count
    css_files = $cssAnalysis.Count
    gsap_js = @($jsAnalysis | Where-Object uses_gsap).Count
    three_js = @($jsAnalysis | Where-Object uses_three).Count
    dynamic_php = @($phpAnalysis | Where-Object dynamic_load_risk).Count
  }
}

$graph | ConvertTo-Json -Depth 6 | Set-Content (Join-Path $outDir 'dependency-graph.json') -Encoding UTF8
@{ generated_at = $graph.generated_at; files = @($phpAnalysis) } | ConvertTo-Json -Depth 6 | Set-Content (Join-Path $outDir 'php-analysis.json') -Encoding UTF8
@{ generated_at = $graph.generated_at; files = @($jsAnalysis) } | ConvertTo-Json -Depth 6 | Set-Content (Join-Path $outDir 'javascript-analysis.json') -Encoding UTF8
@{ generated_at = $graph.generated_at; files = @($cssAnalysis) } | ConvertTo-Json -Depth 6 | Set-Content (Join-Path $outDir 'css-analysis.json') -Encoding UTF8

# Minimal DOT for SVG tooling later
$dot = @('digraph NGT {', '  rankdir=LR;', '  node [shape=box,fontsize=9];')
foreach ($e in ($edges | Select-Object -First 400)) {
  $a = ($e.from -replace '"','')
  $b = ($e.to -replace '"','')
  $dot += ('  "{0}" -> "{1}";' -f $a, $b)
}
$dot += '}'
$dot -join "`n" | Set-Content (Join-Path $outDir 'dependency-graph.dot') -Encoding UTF8

Write-Host "nodes=$($nodes.Count) edges=$($edges.Count) gsap=$($graph.stats.gsap_js) three=$($graph.stats.three_js) dynamic_php=$($graph.stats.dynamic_php)"
