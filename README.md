# FilamentBR Community Licenses

A Laravel application to manage community licenses for Filament PHP packages.

## About

This project is a license management system built specifically for the FilamentBR community. It helps track and manage licenses for various Filament PHP packages.

## Requirements

- PHP 8.2+
- Laravel 12.x
- Node.js & NPM
- Composer

## Installation

1. Clone the repository:
```bash
git clone https://github.com/saade/filamentbr-community-licenses.git
```

2. Install PHP dependencies:
```bash
composer install
```

3. Install JavaScript dependencies:
```bash
npm install
```

4. Copy the environment file:
```bash
cp .env.example .env
```

5. Generate application key:
```bash
php artisan key:generate
```

6. Configure your database in the `.env` file

7. Run migrations:
```bash
php artisan migrate
```

8. Build assets:
```bash
npm run dev
```

## Development

- For development: `npm run dev`
- For production build: `npm run build`

## Testing

Run the tests with:
```bash
php artisan test
```

## Packages Used

### PHP Packages
- Laravel Framework v12.1.0
- Laravel Sanctum v4.0.8
- Laravel Sail v1.41.0
- PHPUnit for testing
- Mockery v1.6.12
- GuzzleHTTP v7.9.2
- Faker v1.24.1
- Monolog v3.8.1

### JavaScript Packages
- Axios v1.7.4
- Vite v6.0.11
- TailwindCSS v3.4.17
- PostCSS v8.5.3
