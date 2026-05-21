# Trigger a Laravel Forge deploy via the site's Deploy Webhook URL.
$ErrorActionPreference = "Stop"

$webhook = $env:FORGE_DEPLOY_WEBHOOK_URL
if (-not $webhook) {
    Write-Host "Set FORGE_DEPLOY_WEBHOOK_URL to the Deploy Webhook from Forge (Site -> Deployment)."
    Write-Host "See docs/FORGE-DEPLOY.md"
    exit 1
}

Write-Host "Triggering Forge deploy..."
Invoke-WebRequest -Uri $webhook -Method GET -UseBasicParsing | Out-Null
Write-Host "Deploy queued. Check Forge -> Site -> Deployments for status."
