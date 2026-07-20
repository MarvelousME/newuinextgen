# Restore zero-byte files from dist/*.zip (run after freeing disk space).
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$temp = Join-Path $env:TEMP "ngt-restore-$(Get-Random)"
New-Item -ItemType Directory -Path $temp -Force | Out-Null
try {
    $maps = @(
        @{ Zip = 'NextGenTutors-BeyondInfinity.zip'; Prefix = 'NextGenTutors-BeyondInfinity' },
        @{ Zip = 'NextGenTutors-Companion.zip'; Prefix = 'NextGenTutors-Companion' }
    )
    foreach ($m in $maps) {
        $zip = Join-Path $root "dist\$($m.Zip)"
        if (-not (Test-Path $zip)) { Write-Warning "Missing $zip"; continue }
        Expand-Archive -Path $zip -DestinationPath $temp -Force
        $src = Join-Path $temp $m.Prefix
        if (-not (Test-Path $src)) { Write-Warning "Bad zip layout: $zip"; continue }
        robocopy $src (Join-Path $root $m.Prefix) /E /XO /R:1 /W:1 | Out-Null
        Write-Host "Restored $($m.Prefix)"
    }
} finally {
    Remove-Item -Recurse -Force $temp -ErrorAction SilentlyContinue
}
Write-Host 'Done. Re-apply carousel/demo fixes if dist predates them.'
