# HTTPS for frosty.diybizrewards.com

Production runs on Laravel Forge (server `1205324`, site `3205937`).

## Current status

- HTTP works: `http://frosty.diybizrewards.com`
- HTTPS fails until nginx listens on port 443 with a valid certificate (the default catch-all rejects TLS handshakes).
- A Let's Encrypt certificate for `frosty.diybizrewards.com` is already on the server at:
  - `/home/forge/ssl/frosty.diybizrewards.com/server.crt`
  - `/home/forge/ssl/frosty.diybizrewards.com/server.key`
- Install script: `/home/forge/install-frosty-ssl.sh` (also in repo: `scripts/install-frosty-ssl.sh`)

## Option A — One command (fastest)

From your machine (you will be prompted for the **forge user's sudo password** on the server):

```bash
ssh -t forge@188.166.230.4 'sudo bash /home/forge/install-frosty-ssl.sh'
```

Then verify:

```bash
curl -I https://frosty.diybizrewards.com/login
```

On the server, refresh Laravel config (already uses `APP_URL=https://...`):

```bash
cd /home/forge/frosty.diybizrewards.com && php artisan config:cache
```

## Option B — Laravel Forge UI

1. Sign in at https://forge.laravel.com
2. Open server **DIYBIZREWARDS** → site **frosty.diybizrewards.com**
3. Go to **SSL** → **Obtain LetsEncrypt Certificate** (domain: `frosty.diybizrewards.com`)
4. Enable **Force HTTPS**

Forge will rewrite nginx and manage renewal automatically.

## Application

`AppServiceProvider` forces `https` URLs in production when `APP_URL` starts with `https://`.

## Renewal (manual cert path)

If you used Option A with the acme.sh certificate, renewal is handled by the forge user's cron (`~/.acme.sh/acme.sh --cron`). After renewal, reload nginx:

```bash
sudo service nginx reload
```
