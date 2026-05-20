# Frosty System

Kilo-based rewards platform (separate from DIY Rewards). Built with Laravel 13.

## Rules

- **Input:** kilograms purchased at store
- **Direct points:** 2 points per kilo
- **Monthly qualification:** 20 kg personal volume (calendar month) for override eligibility
- **Override:** 0.5 points per kilo to qualified uplines (levels 2–4)

## Requirements

- PHP 8.2+
- Composer
- MySQL (Laragon)

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Create database `frosty_rewards` and user `frosty_user`, then set `.env`:

```
APP_NAME=FrostySystem
APP_URL=http://frosty.local
DB_DATABASE=frosty_rewards
DB_USERNAME=frosty_user
DB_PASSWORD=your_password
```

```bash
php artisan migrate
php artisan db:seed
php artisan serve
```

- Dashboard: http://127.0.0.1:8000/
- Store portal: http://127.0.0.1:8000/store/kilos

## License

MIT
