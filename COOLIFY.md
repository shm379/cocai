# Coolify deployment

This project can be deployed in Coolify as a **Docker Compose** application.

## Services

The compose stack contains:

- `app` — Laravel app served through Apache on container port `80`
- `queue` — Laravel queue worker using the database queue driver
- `mysql` — MySQL 8.4 database with persistent storage

## Required Coolify setup

1. Create a new Coolify resource from this Git repository.
2. Choose the Docker Compose build pack and use `docker-compose.yaml`.
3. Assign your public domain to the `app` service. The app listens on container port `80`.
4. Set the required environment variables below.

## Required environment variables

`APP_KEY` is required. Generate it locally and paste the full value into Coolify:

```bash
php artisan key:generate --show
```

Also set:

```env
APP_URL=https://your-domain.com
```

## Optional environment variables

These are already defined in `docker-compose.yaml`, but you can override them in Coolify:

```env
APP_NAME=cocai
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=UTC
DB_DATABASE=cocai
DB_USERNAME=cocai
NABU_BASE_URL=http://localhost:8080
NABU_API_KEY=
NABU_MODEL=nabu-smart
CLASH_API_BASE=
RUN_MIGRATIONS=true
```

## Database passwords

The compose file uses Coolify magic environment variables for database passwords:

```env
SERVICE_PASSWORD_DB
SERVICE_PASSWORD_DB_ROOT
```

Coolify will generate these automatically during deployment.

## Notes

- Do not expose the MySQL service publicly.
- The `app` service runs migrations automatically by default through `RUN_MIGRATIONS=true`.
- The `queue` service is excluded from Coolify health checks because it is a long-running worker.
- Persistent volumes are defined for Laravel storage and MySQL data.
