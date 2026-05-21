# Deploy Frosty_System on Laravel Forge

## 1. Publish (GitHub)

Repository: **https://github.com/gebstechnologies0109a/Frosty_System** (branch `main`)

From your machine:

```powershell
cd c:\laragon\www\Frosty_System
git push origin main
```

Or run `.\publish-to-github.ps1` (requires `gh auth login`).

## 2. Create or connect the Forge site

1. Sign in at https://forge.laravel.com
2. Open your server → **New site** (or edit an existing site)
3. **Source control**: GitHub → `gebstechnologies0109a/Frosty_System` → branch `main`
4. **Web directory**: `public`
5. **PHP**: 8.3+ (match local Laragon)
6. Enable **Quick deploy** (deploy on every push to `main`)

## 3. Deployment script

Site → **Deployment** → replace the default script with the contents of [`forge-deploy.sh`](../forge-deploy.sh) in this repo (or run `bash "$FORGE_SITE_PATH/forge-deploy.sh"` from a short wrapper).

## 4. Environment (.env on server)

Copy from `.env.example` and set at minimum:

| Variable | Production |
|----------|------------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://your-domain.com` |
| `APP_KEY` | Run `php artisan key:generate` once on server |
| `DB_*` | MySQL credentials from Forge database |
| `SESSION_DRIVER` | `database` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `database` |
| `FROSTY_POS_LOGS_PASSWORD` | Strong secret (not the demo default) |

Create the database in Forge, then on the server:

```bash
php artisan migrate --force
php artisan db:seed   # optional demo data; skip on real production
```

## 5. Forge extras

- **Scheduler**: enable “Run scheduler” (runs `* * * * * php artisan schedule:run`)
- **Queue**: add a daemon `php artisan queue:work --sleep=3 --tries=3`
- **SSL**: LetsEncrypt on the site
- **Storage**: `storage:link` runs in `forge-deploy.sh`; ensure `storage/` and `bootstrap/cache/` are writable

## 6. Trigger deploy from Windows (optional)

In Forge: Site → **Deployment** → copy the **Deploy webhook** URL.

```powershell
$env:FORGE_DEPLOY_WEBHOOK_URL = "https://forge.laravel.com/servers/.../sites/.../deploy/http?token=..."
.\deploy-forge.ps1
```

## 7. First-time checklist

- [ ] Site loads `/login`
- [ ] Migrations applied (`php artisan migrate:status`)
- [ ] Vite assets built (`public/build` exists after deploy)
- [ ] POS logs password set in production `.env`
