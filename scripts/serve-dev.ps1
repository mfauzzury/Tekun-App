Set-Location (Join-Path $PSScriptRoot "..\public")
$serverRouter = Join-Path $PSScriptRoot "..\vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php"

# Run PHP built-in server directly so -d upload limits apply to HTTP requests.
# `php artisan serve` spawns a child process without these flags (default 2M breaks IC scans).
& php `
  -d upload_max_filesize=12M `
  -d post_max_size=14M `
  -d memory_limit=256M `
  -d max_execution_time=420 `
  -S 127.0.0.1:8000 `
  $serverRouter
