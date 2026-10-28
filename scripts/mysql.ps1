param([ValidateSet('start','stop','status')][string]$Action = 'status')
$projectRoot = Split-Path -Parent $PSScriptRoot
$mysqlRoot = (Get-ChildItem -LiteralPath (Join-Path $projectRoot '.tools\mysql-package') -Directory | Select-Object -First 1).FullName
if (-not $mysqlRoot) { throw 'Project-local MySQL runtime is missing.' }
$client = [Net.Sockets.TcpClient]::new()
try { $client.Connect('127.0.0.1', 33306); $running = $true } catch { $running = $false } finally { $client.Dispose() }
if ($Action -eq 'status') { Write-Output "Local MySQL 127.0.0.1:33306 running: $running"; exit }
if ($Action -eq 'stop') {
    if ($running) {
        & (Join-Path $mysqlRoot 'bin\mysqladmin.exe') ("--defaults-extra-file=" + (Join-Path $projectRoot '.tools\mysql-admin.cnf')) shutdown
        exit $LASTEXITCODE
    }
    exit
}
if ($running) { Write-Output 'Port 33306 is already listening. No additional server started.'; exit }
$data = Join-Path $projectRoot '.tools\mysql-data'
if (-not (Test-Path -LiteralPath (Join-Path $data 'auto.cnf'))) { throw 'MySQL data directory is not initialized. Do not initialize over existing data.' }
$arguments = @('--no-defaults', ('--basedir="' + $mysqlRoot + '"'), ('--datadir="' + $data + '"'), '--bind-address=127.0.0.1', '--port=33306', '--mysqlx=OFF', '--skip-log-bin')
$process = Start-Process -FilePath (Join-Path $mysqlRoot 'bin\mysqld.exe') -ArgumentList $arguments -WindowStyle Hidden -RedirectStandardOutput (Join-Path $projectRoot 'work\mysql.stdout.log') -RedirectStandardError (Join-Path $projectRoot 'work\mysql.stderr.log') -PassThru
$process.Id | Set-Content (Join-Path $projectRoot '.tools\mysql.pid')
Write-Output "Started local MySQL launcher PID $($process.Id); bound to loopback port 33306."
$readyDeadline = (Get-Date).AddSeconds(30)
do {
    $probe = [Net.Sockets.TcpClient]::new()
    try { $probe.Connect('127.0.0.1', 33306); $ready = $true } catch { $ready = $false } finally { $probe.Dispose() }
    if ($ready) { Write-Output 'Local MySQL is accepting connections.'; exit }
    Start-Sleep -Milliseconds 200
} while ((Get-Date) -lt $readyDeadline)
throw 'Local MySQL did not become ready within 30 seconds. Inspect work/mysql.stderr.log.'
