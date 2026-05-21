# Frosty Rewards System

Laravel 13 rewards platform for Frosty Softserve operators.

## Rules

- Softserve: **2 points** per unit → **₱2** self-rebate (₱1 per point)
- Qualification: **20** personal points per calendar month for override eligibility
- Overrides: Levels **1–4** uplines; each upline must be qualified
- **Main** = `distributors.id = 1` (`is_main = true`)
- Distributors order from Main; never earn rebates

## Demo logins

Password: `password`

| Email | Role |
|-------|------|
| super@frosty.local | Super Admin |
| purchasing@frosty.local | Purchasing Admin |
| finance@frosty.local | Finance Admin |
| it@frosty.local | IT Admin |
| distributor@frosty.local | Distributor |
| ana@frosty.local | Operator |
| ben@frosty.local | Operator (L1 under Ana) |

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

http://127.0.0.1:8000/login

## Deploy (Laravel Forge)

GitHub: https://github.com/gebstechnologies0109a/Frosty_System

See [docs/FORGE-DEPLOY.md](docs/FORGE-DEPLOY.md) for Forge site setup, `.env`, and `forge-deploy.sh`.
