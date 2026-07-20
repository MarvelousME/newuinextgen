#!/usr/bin/env pwsh
<#
.SYNOPSIS
  Generate NGT UI catalog definitions + CSS/JS for remaining Magic UI components.
#>
$ErrorActionPreference = 'Stop'
$root = Resolve-Path (Join-Path $PSScriptRoot '..')
$ui = Join-Path $root 'ui-library'
$catalogDir = Join-Path $ui 'catalog'
$cssDir = Join-Path $ui 'assets\css\components'
$jsDir = Join-Path $ui 'assets\js\components'
New-Item -ItemType Directory -Force -Path $catalogDir, $cssDir, $jsDir | Out-Null

# slug => @{ label; category; kind; needsJs; description }
# kinds: text, button, pattern, card, device, progress, interactive, list, media, map, misc
$defs = [ordered]@{
  'android' = @{ label='Android'; category='devices'; kind='device'; needsJs=$false; desc='Android device mockup frame' }
  'warp-background' = @{ label='Warp Background'; category='backgrounds'; kind='card'; needsJs=$false; desc='Warping gradient card background' }
  'line-shadow-text' = @{ label='Line Shadow Text'; category='text'; kind='text'; needsJs=$false; desc='Moving line-shadow text' }
  'aurora-text' = @{ label='Aurora Text'; category='text'; kind='text'; needsJs=$false; desc='Aurora gradient animated text' }
  'morphing-text' = @{ label='Morphing Text'; category='text'; kind='text'; needsJs=$true; desc='Cycles through words with morph effect' }
  'scroll-progress' = @{ label='Scroll Progress'; category='motion'; kind='progress'; needsJs=$true; desc='Page scroll progress bar' }
  'lens' = @{ label='Lens'; category='interactive'; kind='interactive'; needsJs=$true; desc='Magnifying lens hover effect' }
  'pointer' = @{ label='Pointer'; category='interactive'; kind='interactive'; needsJs=$true; desc='Custom animated pointer' }
  'smooth-cursor' = @{ label='Smooth Cursor'; category='interactive'; kind='interactive'; needsJs=$true; desc='Smoothed custom cursor' }
  'progressive-blur' = @{ label='Progressive Blur'; category='effects'; kind='card'; needsJs=$false; desc='Progressive blur overlay' }
  'neon-gradient-card' = @{ label='Neon Gradient Card'; category='cards'; kind='card'; needsJs=$false; desc='Neon gradient bordered card' }
  'noise-texture' = @{ label='Noise Texture'; category='backgrounds'; kind='pattern'; needsJs=$false; desc='Noise texture overlay' }
  'meteors' = @{ label='Meteors'; category='effects'; kind='pattern'; needsJs=$true; desc='Meteor shower effect' }
  'grid-pattern' = @{ label='Grid Pattern'; category='backgrounds'; kind='pattern'; needsJs=$false; desc='CSS grid background' }
  'hexagon-pattern' = @{ label='Hexagon Pattern'; category='backgrounds'; kind='pattern'; needsJs=$false; desc='Hexagon pattern background' }
  'striped-pattern' = @{ label='Striped Pattern'; category='backgrounds'; kind='pattern'; needsJs=$false; desc='Striped pattern background' }
  'interactive-grid-pattern' = @{ label='Interactive Grid Pattern'; category='backgrounds'; kind='pattern'; needsJs=$true; desc='Hover-reactive grid pattern' }
  'dot-pattern' = @{ label='Dot Pattern'; category='backgrounds'; kind='pattern'; needsJs=$false; desc='Dot pattern background' }
  'flickering-grid' = @{ label='Flickering Grid'; category='backgrounds'; kind='pattern'; needsJs=$true; desc='Flickering grid cells' }
  'hero-video-dialog' = @{ label='Hero Video Dialog'; category='media'; kind='media'; needsJs=$true; desc='Hero video with dialog play' }
  'code-comparison' = @{ label='Code Comparison'; category='media'; kind='media'; needsJs=$false; desc='Before/after code comparison' }
  'globe' = @{ label='Globe'; category='media'; kind='interactive'; needsJs=$true; desc='Lightweight canvas globe fallback' }
  'glare-hover' = @{ label='Glare Hover'; category='effects'; kind='card'; needsJs=$true; desc='Pointer glare hover card' }
  'shimmer-button' = @{ label='Shimmer Button'; category='buttons'; kind='button'; needsJs=$false; desc='Shimmer CTA button' }
  'tweet-card' = @{ label='Tweet Card'; category='cards'; kind='card'; needsJs=$false; desc='Static tweet-style card' }
  'client-tweet-card' = @{ label='Client Tweet Card'; category='cards'; kind='card'; needsJs=$false; desc='Client tweet-style card' }
  'bento-grid' = @{ label='Bento Grid'; category='layouts'; kind='list'; needsJs=$false; desc='Bento feature grid' }
  'particles' = @{ label='Particles'; category='effects'; kind='interactive'; needsJs=$true; desc='Canvas particle field' }
  'number-ticker' = @{ label='Number Ticker'; category='text'; kind='text'; needsJs=$true; desc='Animated number counter' }
  'ripple' = @{ label='Ripple'; category='effects'; kind='pattern'; needsJs=$false; desc='Expanding ripple rings' }
  'retro-grid' = @{ label='Retro Grid'; category='backgrounds'; kind='pattern'; needsJs=$false; desc='Perspective retro grid' }
  'animated-list' = @{ label='Animated List'; category='lists'; kind='list'; needsJs=$true; desc='Staggered animated list' }
  'animated-shiny-text' = @{ label='Animated Shiny Text'; category='text'; kind='text'; needsJs=$false; desc='Shiny sweeping text' }
  'animated-grid-pattern' = @{ label='Animated Grid Pattern'; category='backgrounds'; kind='pattern'; needsJs=$false; desc='Animated grid pattern' }
  'animated-beam' = @{ label='Animated Beam'; category='effects'; kind='interactive'; needsJs=$true; desc='Beam connecting nodes' }
  'text-reveal' = @{ label='Text Reveal'; category='text'; kind='text'; needsJs=$true; desc='Scroll/reveal text' }
  'dia-text-reveal' = @{ label='Dia Text Reveal'; category='text'; kind='text'; needsJs=$true; desc='Diagonal text reveal' }
  'hyper-text' = @{ label='Hyper Text'; category='text'; kind='text'; needsJs=$true; desc='Scramble-to-text effect' }
  'animated-gradient-text' = @{ label='Animated Gradient Text'; category='text'; kind='text'; needsJs=$false; desc='Animated gradient fill text' }
  'orbiting-circles' = @{ label='Orbiting Circles'; category='effects'; kind='interactive'; needsJs=$false; desc='Orbiting icon circles' }
  'dock' = @{ label='Dock'; category='navigation'; kind='list'; needsJs=$true; desc='Mac-style dock navigation' }
  'word-rotate' = @{ label='Word Rotate'; category='text'; kind='text'; needsJs=$true; desc='Rotating words' }
  'avatar-circles' = @{ label='Avatar Circles'; category='social'; kind='list'; needsJs=$false; desc='Stacked avatar group' }
  'typing-animation' = @{ label='Typing Animation'; category='text'; kind='text'; needsJs=$true; desc='Typewriter text' }
  'sparkles-text' = @{ label='Sparkles Text'; category='text'; kind='text'; needsJs=$true; desc='Text with sparkles' }
  'spinning-text' = @{ label='Spinning Text'; category='text'; kind='text'; needsJs=$false; desc='Circular spinning text' }
  'comic-text' = @{ label='Comic Text'; category='text'; kind='text'; needsJs=$false; desc='Comic-style text' }
  'icon-cloud' = @{ label='Icon Cloud'; category='effects'; kind='interactive'; needsJs=$true; desc='Floating icon cloud' }
  'text-animate' = @{ label='Text Animate'; category='text'; kind='text'; needsJs=$true; desc='Letter/word animation' }
  'scroll-based-velocity' = @{ label='Scroll Based Velocity'; category='motion'; kind='text'; needsJs=$true; desc='Velocity-linked scroll text' }
  'shiny-button' = @{ label='Shiny Button'; category='buttons'; kind='button'; needsJs=$false; desc='Shiny CTA button' }
  'shine-border' = @{ label='Shine Border'; category='effects'; kind='card'; needsJs=$false; desc='Animated shine border' }
  'animated-circular-progress-bar' = @{ label='Animated Circular Progress'; category='progress'; kind='progress'; needsJs=$true; desc='Circular progress indicator' }
  'confetti' = @{ label='Confetti'; category='effects'; kind='interactive'; needsJs=$true; desc='Confetti burst on trigger' }
  'cool-mode' = @{ label='Cool Mode'; category='effects'; kind='interactive'; needsJs=$true; desc='Click particle cool-mode' }
  'pulsating-button' = @{ label='Pulsating Button'; category='buttons'; kind='button'; needsJs=$false; desc='Pulsating CTA' }
  'ripple-button' = @{ label='Ripple Button'; category='buttons'; kind='button'; needsJs=$true; desc='Click ripple button' }
  'file-tree' = @{ label='File Tree'; category='media'; kind='list'; needsJs=$false; desc='File tree display' }
  'blur-fade' = @{ label='Blur Fade'; category='effects'; kind='card'; needsJs=$true; desc='Blur-to-clear fade-in' }
  'safari' = @{ label='Safari'; category='devices'; kind='device'; needsJs=$false; desc='Safari browser frame' }
  'iphone' = @{ label='iPhone'; category='devices'; kind='device'; needsJs=$false; desc='iPhone device frame' }
  'rainbow-button' = @{ label='Rainbow Button'; category='buttons'; kind='button'; needsJs=$false; desc='Rainbow border button' }
  'interactive-hover-button' = @{ label='Interactive Hover Button'; category='buttons'; kind='button'; needsJs=$false; desc='Interactive hover CTA' }
  'terminal' = @{ label='Terminal'; category='media'; kind='media'; needsJs=$true; desc='Terminal typing window' }
  'video-text' = @{ label='Video Text'; category='text'; kind='text'; needsJs=$false; desc='Text masked over video/gradient' }
  'pixel-image' = @{ label='Pixel Image'; category='media'; kind='media'; needsJs=$true; desc='Pixelated image reveal' }
  'highlighter' = @{ label='Highlighter'; category='text'; kind='text'; needsJs=$false; desc='Highlighter mark effect' }
  'animated-theme-toggler' = @{ label='Animated Theme Toggler'; category='interactive'; kind='button'; needsJs=$true; desc='Theme toggle control' }
  'light-rays' = @{ label='Light Rays'; category='backgrounds'; kind='pattern'; needsJs=$false; desc='Light rays background' }
  'dotted-map' = @{ label='Dotted Map'; category='media'; kind='map'; needsJs=$false; desc='Dotted world map panel' }
  'backlight' = @{ label='Backlight'; category='effects'; kind='card'; needsJs=$false; desc='Backlight glow panel' }
  'kinetic-text' = @{ label='Kinetic Text'; category='text'; kind='text'; needsJs=$true; desc='Kinetic letter motion' }
  'text-3d-flip' = @{ label='Text 3D Flip'; category='text'; kind='text'; needsJs=$true; desc='3D flip text cycle' }
  'animated-subscribe-button' = @{ label='Animated Subscribe Button'; category='buttons'; kind='button'; needsJs=$true; desc='Subscribe button with state' }
}

# Emit PHP registry array
$phpLines = @()
$phpLines += '<?php'
$phpLines += '/**'
$phpLines += ' * Auto-generated Magic UI component catalog (MIT reimplementations).'
$phpLines += ' * Do not enqueue React. Generated by scripts/generate-magicui-catalog.ps1'
$phpLines += ' *'
$phpLines += ' * @package NGT_UI'
$phpLines += ' */'
$phpLines += ''
$phpLines += 'declare(strict_types=1);'
$phpLines += ''
$phpLines += 'return array('

foreach ($slug in $defs.Keys) {
  $d = $defs[$slug]
  $needs = if ($d.needsJs) { 'true' } else { 'false' }
  $phpLines += "	'$slug' => array("
  $phpLines += "		'label'       => '" + ($d.label -replace "'","\'") + "',"
  $phpLines += "		'category'    => '$($d.category)',"
  $phpLines += "		'kind'        => '$($d.kind)',"
  $phpLines += "		'needs_js'    => $needs,"
  $phpLines += "		'description' => '" + ($d.desc -replace "'","\'") + "',"
  $phpLines += "		'source'      => 'magicui',"
  $phpLines += "		'licence'     => 'MIT',"
  $phpLines += '	),'
}
$phpLines += ');'
$phpLines += ''

Set-Content -Path (Join-Path $catalogDir 'components.php') -Value ($phpLines -join "`n") -Encoding UTF8

# Also JSON for audits
$jsonObj = [ordered]@{}
foreach ($slug in $defs.Keys) { $jsonObj[$slug] = $defs[$slug] }
$jsonObj | ConvertTo-Json -Depth 4 | Set-Content (Join-Path $catalogDir 'components.json') -Encoding UTF8

Write-Host ("CATALOG_COUNT=" + $defs.Count)
Write-Host ("WROTE " + (Join-Path $catalogDir 'components.php'))
