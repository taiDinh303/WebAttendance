$projectRoot = Split-Path -Parent $PSScriptRoot
$phpPath = Join-Path $projectRoot '.tools\php\php.exe'
$composerPath = Join-Path $projectRoot '.tools\composer.phar'
$env:PATH = (Split-Path -Parent $phpPath) + [IO.Path]::PathSeparator + $env:PATH
Push-Location (Join-Path $projectRoot 'attendance_api')
try {
    & $phpPath $composerPath validate --strict
    if ($LASTEXITCODE -ne 0) { throw 'Composer validation failed.' }
    & $phpPath vendor/bin/pint --format=agent
    if ($LASTEXITCODE -ne 0) { throw 'PHP formatting failed.' }
    & $phpPath vendor/bin/phpunit
    if ($LASTEXITCODE -ne 0) { throw 'API tests failed.' }
    & $phpPath $composerPath audit --locked
    if ($LASTEXITCODE -ne 0) { throw 'Dependency audit failed.' }
} finally {
    Pop-Location
}
