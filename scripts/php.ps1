# Use the project-local PHP runtime without modifying the machine PATH.
$projectRoot = Split-Path -Parent $PSScriptRoot
$phpPath = Join-Path $projectRoot '.tools\php\php.exe'
if (-not (Test-Path -LiteralPath $phpPath)) {
    throw 'PHP runtime missing. Install PHP 8.4+ or provision .tools/php; see docs/10-setup-local.md.'
}
& $phpPath @args
exit $LASTEXITCODE
