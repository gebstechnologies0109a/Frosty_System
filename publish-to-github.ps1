# Publish Frosty_System to gebstechnologies0109a/Frosty_System on GitHub.
$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot

$owner = "gebstechnologies0109a"
$repo = "Frosty_System"
$remote = "https://github.com/$owner/$repo.git"

gh auth status | Out-Null
if ($LASTEXITCODE -ne 0) {
    Write-Host "Not logged in. Run: gh auth login --hostname github.com --git-protocol https"
    Write-Host "Sign in as $owner at https://github.com/login/device"
    exit 1
}

$view = gh repo view "$owner/$repo" 2>$null
if ($LASTEXITCODE -ne 0) {
    Write-Host "Creating $owner/$repo ..."
    gh repo create "$owner/$repo" --public --description "Kilo-based Frosty rewards system (Laravel)"
}

git remote remove origin 2>$null
git remote add origin $remote
git push -u origin main

Write-Host "Done: https://github.com/$owner/$repo"
