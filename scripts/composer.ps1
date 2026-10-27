$projectRoot = Split-Path -Parent $PSScriptRoot
$phpPath = Join-Path $projectRoot '.tools\php\php.exe'
$composerPath = Join-Path $projectRoot '.tools\composer.phar'
if (-not (Test-Path -LiteralPath $phpPath) -or -not (Test-Path -LiteralPath $composerPath)) {
    throw 'Project-local PHP/Composer missing; see docs/10-setup-local.md.'
}
$env:PATH = (Split-Path -Parent $phpPath) + [IO.Path]::PathSeparator + $env:PATH
& $phpPath $composerPath @args
exit $LASTEXITCODE
