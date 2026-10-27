$projectRoot = Split-Path -Parent $PSScriptRoot
$phpPath = Join-Path $projectRoot '.tools\php\php.exe'
Push-Location (Join-Path $projectRoot 'attendance_api')
try {
    & $phpPath artisan @args
    $result = $LASTEXITCODE
} finally {
    Pop-Location
}
exit $result
