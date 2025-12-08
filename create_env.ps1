<#
create_env.ps1
Interactive PowerShell helper to create a local .env file for Ridesphere.
This script runs locally on your machine, prompts securely for SMTP credentials
and writes a `.env` file in the current folder. The script does NOT send or
upload your credentials anywhere.

Usage (PowerShell):
  cd C:\xampp\htdocs\ridesphere
  .\create_env.ps1

You will be prompted for values. Press Enter to accept defaults shown in brackets.
#>

Write-Host "This script creates a local .env file for Ridesphere (kept out of git)." -ForegroundColor Cyan

function Read-Default([string]$prompt, [string]$default) {
    $val = Read-Host "$prompt [$default]"
    if ([string]::IsNullOrWhiteSpace($val)) { return $default }
    return $val
}

$smtpHost = Read-Default "SMTP host" "smtp.gmail.com"
$smtpPort = Read-Default "SMTP port" "587"
$smtpUser = Read-Host -Prompt "SMTP username (email)"

Write-Host "Enter SMTP password (App Password). Input will be hidden:" -NoNewline
$securePass = Read-Host -AsSecureString
# convert secure string to plain text for writing to .env (file stays local)
$marshal = [Runtime.InteropServices.Marshal]
$bstr = $marshal::SecureStringToBSTR($securePass)
$smtpPass = $marshal::PtrToStringAuto($bstr)
$marshal::ZeroFreeBSTR($bstr)

$smtpSecure = Read-Default "SMTP secure (tls|ssl)" "tls"
$fromEmail = Read-Default "From email" $smtpUser
$fromName = Read-Default "From name" "Ridesphere"

$envPath = Join-Path -Path (Get-Location) -ChildPath ".env"

Write-Host "Writing .env to $envPath" -ForegroundColor Yellow

$content = @()
$content += "SMTP_HOST=$smtpHost"
$content += "SMTP_PORT=$smtpPort"
$content += "SMTP_USER=$smtpUser"
$content += "SMTP_PASS=$smtpPass"
$content += "SMTP_SECURE=$smtpSecure"
$content += "FROM_EMAIL=$fromEmail"
$content += "FROM_NAME=$fromName"

Set-Content -Path $envPath -Value $content -Encoding UTF8

Write-Host "Created .env (not tracked by git)." -ForegroundColor Green
Write-Host "Next: run the TEST_SMTP.php script to validate connectivity:" -ForegroundColor Cyan
Write-Host "  php -f \"$(Join-Path (Get-Location) 'TEST_SMTP.php')\"" -ForegroundColor White

exit 0
