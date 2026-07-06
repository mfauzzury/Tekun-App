# Opens an SSH tunnel so Laravel can reach the private MySQL host via the bastion.
# Keep this window open while developing. Matches MySQL Workbench "Standard TCP/IP over SSH".

$ErrorActionPreference = "Stop"

$sshKey = "C:\Tekun\bastion-key.pem"
$bastion = "ec2-user@43.217.107.131"
$remoteDbHost = "ip-10-103-0-101.ap-southeast-5.compute.internal"
$localPort = 3307
$remotePort = 3306

if (-not (Test-Path $sshKey)) {
    Write-Error "SSH key not found: $sshKey"
}

# On Windows, SSH rejects keys with overly broad ACLs. If you see "UNPROTECTED PRIVATE KEY FILE":
#   icacls $sshKey /inheritance:r
#   icacls $sshKey /grant:r "$env:USERNAME`:R"
#   icacls $sshKey /remove "BUILTIN\Users"

Write-Host "Starting DB tunnel: localhost:$localPort -> $remoteDbHost`:$remotePort (via $bastion)"
Write-Host "Press Ctrl+C to stop."

ssh -i $sshKey -L "${localPort}:${remoteDbHost}:${remotePort}" $bastion -N
