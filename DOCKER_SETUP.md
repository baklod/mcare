# MCARE Docker Development Setup

This setup keeps the existing XAMPP workflow intact while providing a consistent, isolated Docker environment for the 65%-prototype branch.

## What runs

| Service | Pinned runtime | Default host port | Memory limit | Notes |
| --- | --- | ---: | ---: | --- |
| app | PHP 8.2.32 + Composer 2.10.1 | 127.0.0.1:8000 | 768 MB | Laravel development server; no extra web server |
| database | MariaDB 10.11.18 LTS | 127.0.0.1:3307 | 512 MB | Uses only mcare_docker_dev in a named volume |
| vite | Node 24.18.0 | 127.0.0.1:5173 | 768 MB | Optional frontend profile |

The normal two-service environment has a 1.25 GB container limit. With Vite, the combined limit is 2 GB. Docker Desktop and WSL overhead plus build caches can bring total development usage close to 2-3 GB.

MariaDB uses a 192 MB InnoDB buffer pool, 40 maximum connections, no performance schema, and rotated logs. No Redis, Mailpit, Selenium, phpMyAdmin, queue worker, debugger, or monitoring service is included because MCARE does not currently require one for its synchronous database notifications and local log mailer.

## Safety boundaries

- Docker reads .env.docker, which is Git-ignored. The repository existing .env is not overwritten.
- .env.docker is mounted over /var/www/html/.env, masking the host .env inside the PHP and Node containers.
- Database environment variables override Laravel host and XAMPP database settings only inside containers.
- Docker MariaDB is exposed on 127.0.0.1:3307; XAMPP MariaDB remains on 3306.
- The Docker database is mcare_docker_dev in the named volume mcare-docker-dev_mcare_database.
- The scripts never run migrate:fresh, db:wipe, destructive SQL, docker compose down, or volume deletion.
- The setup does not use privileged containers, added capabilities, or the Docker socket.
- PayMongo, Google OAuth, mail, and production secrets are blank by default.

Do not run docker compose down --volumes or docker volume rm unless the team has intentionally decided to delete its isolated Docker development data.

## Docker and WSL prerequisite

Docker and WSL were not installed on the audited laptop on 2026-08-17. The repository files are ready, but live container verification requires installation first.

These are manual, machine-wide steps. The setup script does not perform them:

1. Close memory-heavy applications and save work. An 8 GB laptop should have several GB free before the first image build.
2. Open an elevated PowerShell window and install the WSL platform without choosing a Linux distribution:

   ~~~powershell
   wsl --install --no-distribution
   ~~~

3. Restart Windows if requested, then run:

   ~~~powershell
   wsl --update
   wsl --status
   wsl --version
   ~~~

4. Download Docker Desktop only from the official Docker website. Choose the per-user WSL 2 backend and Linux containers. Do not enable Kubernetes.
5. Start Docker Desktop, wait until its engine is ready, and verify:

   ~~~powershell
   docker version
   docker compose version
   docker info
   ~~~

Official references:

- [Install WSL](https://learn.microsoft.com/windows/wsl/install)
- [Install Docker Desktop on Windows](https://docs.docker.com/desktop/setup/install/windows-install/)
- [Docker Desktop WSL 2 backend](https://docs.docker.com/desktop/features/wsl/)

## Optional 8 GB WSL cap

The following is a recommendation only. It affects every WSL 2 workload on the laptop, so review it with the laptop owner before creating or changing %UserProfile%/.wslconfig:

~~~ini
[wsl2]
memory=3GB
processors=2
swap=1GB

[experimental]
autoMemoryReclaim=gradual
~~~

Applying a changed WSL configuration requires wsl --shutdown; do not apply it while other WSL work is running.

## First run after cloning

Open PowerShell in the repository:

~~~powershell
git switch "65%-prototype"
git status
powershell.exe -NoProfile -ExecutionPolicy Bypass -File ./scripts/docker-setup.ps1
~~~

The idempotent script:

1. Preserves existing Git changes and any existing .env.docker.
2. Copies .env.docker.example only if .env.docker is missing.
3. Generates a random local database password only for a newly copied file.
4. Validates Compose and builds the PHP image.
5. Starts only the isolated MariaDB service.
6. Installs Composer and npm dependencies in named volumes.
7. Generates APP_KEY only when it is missing.
8. Runs normal php artisan migrate.
9. Builds Vite assets.
10. Starts Laravel and waits for health checks.

It does not seed by default. To review and explicitly confirm local demo accounts:

~~~powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File ./scripts/docker-setup.ps1 -Seed
~~~

The script explains that the seeder creates the five local-only accounts listed in TEAM_SETUP_GUIDE.md, all with password123, plus sample Career Hub opportunities. It runs only after the user types SEED-LOCAL-DEMO.

For frontend live reload:

~~~powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File ./scripts/docker-setup.ps1 -WithVite
~~~

## Normal startup

Backend only:

~~~powershell
docker compose --env-file .env.docker up -d --wait
~~~

With Vite:

~~~powershell
docker compose --env-file .env.docker --profile frontend up -d --wait
~~~

Stop containers without deleting them or their data:

~~~powershell
docker compose --env-file .env.docker stop
~~~

## URLs and database

- Landing page: http://127.0.0.1:8000
- Shared role login: http://127.0.0.1:8000/login
- Laravel health route: http://127.0.0.1:8000/up
- Vite when enabled: http://127.0.0.1:5173
- Docker MariaDB from Windows tools: 127.0.0.1:3307
- Docker MariaDB from containers: database:3306
- Docker database name: mcare_docker_dev

Change the three host ports only in .env.docker if a local port is occupied.

## Daily commands

~~~powershell
# Service and health status
docker compose --env-file .env.docker ps

# Follow Laravel logs
docker compose --env-file .env.docker logs -f app

# Apply new, normal migrations
docker compose --env-file .env.docker exec app php artisan migrate

# Run the Laravel suite
docker compose --env-file .env.docker exec app php artisan test

# Rebuild frontend assets without leaving Vite running
docker compose --env-file .env.docker --profile frontend run --rm --no-deps vite npm run build

# Start or stop only Vite
docker compose --env-file .env.docker --profile frontend up -d --wait vite
docker compose --env-file .env.docker stop vite

# Actual one-time RAM snapshot
docker stats --no-stream
~~~

When composer.lock or package-lock.json changes:

~~~powershell
docker compose --env-file .env.docker run --rm --no-deps app composer install
docker compose --env-file .env.docker --profile frontend run --rm --no-deps vite npm ci
~~~

## Verification checklist

After Docker is available, run:

~~~powershell
docker compose --env-file .env.docker config --quiet
docker compose --env-file .env.docker build
docker compose --env-file .env.docker up -d --wait
docker compose --env-file .env.docker ps
docker compose --env-file .env.docker exec app php artisan migrate:status
docker compose --env-file .env.docker exec app php artisan test
docker compose --env-file .env.docker --profile frontend run --rm --no-deps vite npm run build
docker stats --no-stream
~~~

Check http://127.0.0.1:8000, /login, /admin/login, /trainer/login, and /trainee/login. Seeded demo accounts can then verify redirects and authorization for admin, trainer, trainee, applicant, and alumni.

Finish verification without deleting data:

~~~powershell
docker compose --env-file .env.docker stop
~~~

## XAMPP fallback

The existing XAMPP workflow remains available. Stop the Docker containers, keep the repository original .env configured for mcare_db on 127.0.0.1:3306, and use the commands in TEAM_SETUP_GUIDE.md. Docker does not import, modify, or delete the XAMPP database.

## 8 GB laptop limitations

- The first build and npm ci can be CPU- and disk-intensive. Close browsers, IDE windows, and XAMPP when they are not needed.
- Keep Vite stopped unless actively editing frontend files; compiled assets remain in public/build.
- Docker image layers and named volumes will typically need roughly 1.5-3 GB initially, then grow with uploaded files and database data.
- Source files live on a Windows bind mount. Large dependency trees are kept in Linux named volumes for better performance, but filesystem-heavy Vite refreshes may still be slower than a repository stored inside WSL.
- Docker Desktop Resource Saver is compatible because services use no automatic restart policy. Stop the project when finished.
