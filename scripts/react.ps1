$projectRoot=Split-Path -Parent $PSScriptRoot
Push-Location (Join-Path $projectRoot 'atd_react')
try { & npm @args; $result=$LASTEXITCODE } finally { Pop-Location }
exit $result
