# Split ui-library/assets/css/components/catalog.css into per-kind CSS files.
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$catalog = Join-Path $root 'ui-library/assets/css/components/catalog.css'
$kindsDir = Join-Path $root 'ui-library/assets/css/kinds'
$content = Get-Content -Raw -Path $catalog

$markers = [ordered]@{
    'button'      = '(?s)/\* —— Buttons —— \*/.*?(?=/\* —— Text effects —— \*/)'
    'text'        = '(?s)/\* —— Text effects —— \*/.*?(?=/\* —— Patterns / backgrounds —— \*/)'
    'pattern'     = '(?s)/\* —— Patterns / backgrounds —— \*/.*?(?=/\* —— Cards —— \*/)'
    'card'        = '(?s)/\* —— Cards —— \*/.*?(?=/\* —— Devices —— \*/)'
    'device'      = '(?s)/\* —— Devices —— \*/.*?(?=/\* —— Progress —— \*/)'
    'progress'    = '(?s)/\* —— Progress —— \*/.*?(?=/\* —— Lists / layouts —— \*/)'
    'list'        = '(?s)/\* —— Lists / layouts —— \*/.*?(?=/\* —— Media —— \*/)'
    'media'       = '(?s)/\* —— Media —— \*/.*?(?=/\* —— Interactive —— \*/)'
    'interactive' = '(?s)/\* —— Interactive —— \*/.*?(?=\.ngt-ui-blur-fade|\@media \(prefers-reduced-motion)'
    'misc'        = '(?s)(\.ngt-ui-blur-fade.*?(?=\@media \(prefers-reduced-motion))'
}

foreach ($kind in $markers.Keys) {
    $pattern = $markers[$kind]
    if ($content -match $pattern) {
        $block = $Matches[0].Trim()
        $out = Join-Path $kindsDir "kind-$kind.css"
        Set-Content -Path $out -Encoding UTF8 -Value "/* Kind: $kind — split from catalog.css */`n$block"
        Write-Host "Wrote $out"
    } else {
        Write-Warning "No match for kind-$kind"
    }
}

$base = @'
/* NGT UI Catalog — base shell (kind rules in kind-*.css) */

.ngt-ui-comp {
  box-sizing: border-box;
  position: relative;
  color: var(--ngt-color-text, #111);
  font-family: "Source Sans 3", "Segoe UI", system-ui, sans-serif;
}
.ngt-ui-comp *,
.ngt-ui-comp *::before,
.ngt-ui-comp *::after { box-sizing: border-box; }

'@

$tail = @'

@media (prefers-reduced-motion: reduce) {
  .ngt-ui-comp *,
  .ngt-ui-comp *::before,
  .ngt-ui-comp *::after {
    animation: none !important;
    transition: none !important;
  }
}

@media (max-width: 640px) {
  .ngt-ui-media--code-comparison,
  .ngt-ui-list--bento-grid { grid-template-columns: 1fr; }
  .ngt-ui-bento__cell:nth-child(1) { grid-column: auto; }
}
'@

Set-Content -Path $catalog -Encoding UTF8 -Value ($base + $tail)
Write-Host "Trimmed catalog.css to base + media queries"
