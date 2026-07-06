$ErrorActionPreference = "Stop"

$repoRoot = Resolve-Path (Join-Path $PSScriptRoot "..")
$linkPath = Join-Path $repoRoot "public\storage"
$targetPath = Join-Path $repoRoot "storage\app\public"

function Test-StorageLinkCorrect {
    param(
        [string]$Link,
        [string]$Target
    )

    if (-not (Test-Path $Link)) {
        return $false
    }

    $item = Get-Item $Link -Force
    if ($item.LinkType -notin @("Junction", "SymbolicLink")) {
        return $false
    }

    try {
        $resolvedTarget = (Resolve-Path $item.Target[0]).Path
        $resolvedExpected = (Resolve-Path $Target).Path
    } catch {
        return $false
    }

    return $resolvedTarget -eq $resolvedExpected
}

Set-Location $repoRoot

if (Test-StorageLinkCorrect -Link $linkPath -Target $targetPath) {
    Write-Host "Storage link OK: $linkPath"
    exit 0
}

if (Test-Path $linkPath) {
    Remove-Item $linkPath -Force -Recurse
}

& php artisan storage:link --force
exit $LASTEXITCODE
