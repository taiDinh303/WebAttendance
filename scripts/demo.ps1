param([ValidateSet('api','web','status')][string]$Service = 'status')
$projectRoot = Split-Path -Parent $PSScriptRoot
$phpPath = Join-Path $projectRoot '.tools/php/php.exe'
$apiRoot = Join-Path $projectRoot 'attendance_api'
$webRoot = Join-Path $projectRoot 'atd_react'

if ($Service -eq 'status') {
    $status = @{}
    foreach ($port in @(8000, 5173)) {
        $client = [Net.Sockets.TcpClient]::new()
        try { $client.Connect('127.0.0.1', $port); $status["port$port"] = $true } catch { $status["port$port"] = $false } finally { $client.Dispose() }
    }
    $status.url = 'http://127.0.0.1:5173'
    $status.database = 'attendance_demo'
    $status | ConvertTo-Json
    exit
}
if (-not (Test-Path -LiteralPath (Join-Path $apiRoot '.env.demo'))) {
    throw 'Missing ignored attendance_api/.env.demo. Provision the isolated demo first; see docs/10-setup-local.md.'
}
if (Test-Path -LiteralPath (Join-Path $apiRoot 'bootstrap/cache/config.php')) {
    throw 'A config cache is present. Clear the intended environment cache explicitly before running the local demo.'
}
if ($Service -eq 'api') {
    Push-Location $apiRoot
    try {
        & $phpPath artisan app:diagnose --env=demo --no-ansi
        if ($LASTEXITCODE -ne 0) { throw 'Demo environment is not ready. Start local MySQL and apply the demo migrations first.' }
        & $phpPath artisan serve --env=demo --host=127.0.0.1 --port=8000 --no-reload
    } finally { Pop-Location }
} else {
    $previousProxy = $env:API_PROXY_TARGET
    Push-Location $webRoot
    try {
        $env:API_PROXY_TARGET = 'http://127.0.0.1:8000'
        & node node_modules/vite/bin/vite.js --host 127.0.0.1 --port 5173 --strictPort
    } finally { $env:API_PROXY_TARGET = $previousProxy; Pop-Location }
}
