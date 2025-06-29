# gestionalecv

This project is intended to run a Laravel application using Docker for local development.

## Requirements

- [Docker](https://www.docker.com/) and [Docker Compose](https://docs.docker.com/compose/) installed.

## Getting Started

1. Copy `env.example` to `.env` and adjust settings as necessary.
2. Build and start the containers:

   ```bash
   docker compose up -d
   ```

3. Install dependencies and generate the application key:

   ```bash
   docker compose exec app composer install
   docker compose exec app php artisan key:generate
   ```

4. Access the application at `http://localhost`.

> **Note**: The actual Laravel source code is not included in this repository due to environment restrictions. After cloning, you may initialize the Laravel framework yourself inside this directory using Composer:
>
> ```bash
> docker compose run --rm app composer create-project laravel/laravel .
> ```

