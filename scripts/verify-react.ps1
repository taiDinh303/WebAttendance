$projectRoot=Split-Path -Parent $PSScriptRoot
Push-Location (Join-Path $projectRoot 'atd_react')
try {
    foreach($task in @('typecheck','lint','test','build')) {
        & npm run $task
        if($LASTEXITCODE -ne 0) { throw "React check failed: $task" }
    }
} finally { Pop-Location }
