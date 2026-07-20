# Regenerate UI library inventories and run verification
param(
    [switch]$VerifyOnly
)

$Root = Split-Path -Parent $PSScriptRoot
Set-Location $Root

if (-not $VerifyOnly) {
    Write-Host "Analyzing HTML artifacts and old theme..."
    node "$Root\scripts\analyze-ui-artifacts.mjs"
}

Write-Host "Verifying UI library (hardcode scan)..."
php "$Root\NextGenTutors-Companion\scripts\verify-ui-library.php"
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-Host "Companion validate..."
php "$Root\NextGenTutors-Companion\scripts\validate.php"
exit $LASTEXITCODE
