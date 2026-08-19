[CmdletBinding()]
param(
    [switch] $WithVite,
    [switch] $Seed
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$dockerEnvPath = Join-Path $projectRoot '.env.docker'
$dockerEnvExamplePath = Join-Path $projectRoot '.env.docker.example'
$script:DockerEnvRelative = '.env.docker'

function Invoke-DockerCompose {
    param(
        [Parameter(ValueFromRemainingArguments = $true)]
        [string[]] $ComposeArguments
    )

    & docker compose --env-file $script:DockerEnvRelative @ComposeArguments

    if ($LASTEXITCODE -ne 0) {
        throw "docker compose failed: $($ComposeArguments -join ' ')"
    }
}

function Get-DotEnvValue {
    param(
        [string] $Path,
        [string] $Name
    )

    $pattern = '^' + [regex]::Escape($Name) + '=(.*)$'

    foreach ($line in [IO.File]::ReadAllLines($Path)) {
        if ($line -match $pattern) {
            return $matches[1].Trim().Trim('"')
        }
    }

    return ''
}

function Set-DotEnvValue {
    param(
        [string] $Path,
        [string] $Name,
        [string] $Value
    )

    $pattern = '^' + [regex]::Escape($Name) + '='
    $found = $false
    $updatedLines = foreach ($line in [IO.File]::ReadAllLines($Path)) {
        if ($line -match $pattern) {
            $found = $true
            "$Name=$Value"
        } else {
            $line
        }
    }

    if (-not $found) {
        $updatedLines += "$Name=$Value"
    }

    $utf8WithoutBom = New-Object Text.UTF8Encoding($false)
    [IO.File]::WriteAllLines($Path, $updatedLines, $utf8WithoutBom)
}

function New-RandomHexSecret {
    $bytes = New-Object byte[] 24
    $generator = [Security.Cryptography.RandomNumberGenerator]::Create()

    try {
        $generator.GetBytes($bytes)
    } finally {
        $generator.Dispose()
    }

    return -join ($bytes | ForEach-Object { $_.ToString('x2') })
}

Push-Location $projectRoot

try {
    Write-Host 'MCARE Docker setup: safe local development bootstrap' -ForegroundColor Cyan

    if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
        throw 'Docker is not installed or is not on PATH. See DOCKER_SETUP.md; this script does not install Docker or change WSL.'
    }

    & docker info --format '{{.ServerVersion}}' | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw 'Docker is installed, but its engine is not available. Start Docker Desktop and retry.'
    }

    & docker compose version | Out-Null
    if ($LASTEXITCODE -ne 0) {
        throw 'Docker Compose v2 is required.'
    }

    if (Get-Command git -ErrorAction SilentlyContinue) {
        $branch = (& git branch --show-current).Trim()
        if ($branch -ne '65%-prototype') {
            Write-Warning "Current branch is '$branch'; expected '65%-prototype'. No branch switch was performed."
        }

        $startingStatus = & git status --short
        if ($startingStatus) {
            Write-Host 'Existing Git changes detected; they will be preserved:' -ForegroundColor Yellow
            $startingStatus | ForEach-Object { Write-Host "  $_" }
        }
    }

    $createdDockerEnv = $false
    if (-not (Test-Path -LiteralPath $dockerEnvPath)) {
        Copy-Item -LiteralPath $dockerEnvExamplePath -Destination $dockerEnvPath
        $createdDockerEnv = $true
        Set-DotEnvValue -Path $dockerEnvPath -Name 'DOCKER_DB_PASSWORD' -Value (New-RandomHexSecret)
        Write-Host 'Created .env.docker from the example with a random local database password.'
    } else {
        Write-Host 'Preserving the existing .env.docker file.'
    }

    $databasePassword = Get-DotEnvValue -Path $dockerEnvPath -Name 'DOCKER_DB_PASSWORD'
    if ([string]::IsNullOrWhiteSpace($databasePassword) -or $databasePassword -eq 'SETUP_GENERATES_A_RANDOM_LOCAL_PASSWORD') {
        if ($createdDockerEnv) {
            throw 'The generated Docker database password was unexpectedly empty.'
        }

        throw 'Existing .env.docker still has an empty/placeholder database password. Edit that file manually; the script did not overwrite it.'
    }

    Write-Host 'Validating Compose configuration...'
    Invoke-DockerCompose config --quiet

    Write-Host 'Building the pinned PHP development image...'
    Invoke-DockerCompose build app

    Write-Host 'Starting only the isolated MariaDB service...'
    Invoke-DockerCompose up -d --wait --wait-timeout 120 database

    Write-Host 'Installing PHP dependencies in the Docker vendor volume...'
    Invoke-DockerCompose run --rm --no-deps app composer install --no-interaction --prefer-dist --optimize-autoloader

    Write-Host 'Preparing persistent Laravel storage directories...'
    Invoke-DockerCompose run --rm --no-deps app sh -lc 'mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache'

    $applicationKey = Get-DotEnvValue -Path $dockerEnvPath -Name 'APP_KEY'
    if ([string]::IsNullOrWhiteSpace($applicationKey)) {
        Write-Host 'Generating a Laravel application key because APP_KEY is missing...'
        $keyOutput = & docker compose --env-file $script:DockerEnvRelative run --rm --no-deps app php artisan key:generate --show --no-ansi 2>&1
        if ($LASTEXITCODE -ne 0) {
            throw 'Laravel application key generation failed.'
        }

        $keyMatch = [regex]::Matches(($keyOutput -join [Environment]::NewLine), 'base64:[A-Za-z0-9+/=]+') | Select-Object -Last 1
        if (-not $keyMatch) {
            throw 'Laravel did not return a recognizable application key.'
        }

        Set-DotEnvValue -Path $dockerEnvPath -Name 'APP_KEY' -Value $keyMatch.Value
        Write-Host 'Stored the generated key only in ignored .env.docker.'
    } else {
        Write-Host 'Existing Docker APP_KEY preserved.'
    }

    Write-Host 'Running normal, non-destructive migrations on mcare_docker_dev...'
    Invoke-DockerCompose run --rm app php artisan migrate --force --no-interaction

    if ($Seed) {
        Write-Warning 'Seeding is optional. DatabaseSeeder creates only local demo accounts (admin, trainer, trainee, applicant, alumni; password password123) and sample Career Hub opportunities.'
        $seedApproval = Read-Host 'Type SEED-LOCAL-DEMO to continue, or press Enter to skip'
        if ($seedApproval -eq 'SEED-LOCAL-DEMO') {
            Invoke-DockerCompose run --rm app php artisan db:seed --force --no-interaction
        } else {
            Write-Host 'Seeder skipped; no seed data was written.'
        }
    } else {
        Write-Host 'Seeder skipped. Re-run with -Seed to review and confirm local demo data.'
    }

    Write-Host 'Installing Node dependencies in the Docker node_modules volume...'
    Invoke-DockerCompose --profile frontend run --rm --no-deps vite npm ci --no-audit --no-fund

    Write-Host 'Building Vite production assets...'
    Invoke-DockerCompose --profile frontend run --rm --no-deps vite npm run build

    Write-Host 'Clearing Laravel development caches...'
    Invoke-DockerCompose run --rm app php artisan optimize:clear --no-interaction

    Write-Host 'Starting the Laravel application and waiting for its health check...'
    Invoke-DockerCompose up -d --wait --wait-timeout 120 app

    if ($WithVite) {
        Write-Host 'Starting the optional Vite development service...'
        Invoke-DockerCompose --profile frontend up -d --wait --wait-timeout 120 vite
    }

    Invoke-DockerCompose ps

    $appPort = Get-DotEnvValue -Path $dockerEnvPath -Name 'DOCKER_APP_PORT'
    $vitePort = Get-DotEnvValue -Path $dockerEnvPath -Name 'DOCKER_VITE_PORT'
    $databasePort = Get-DotEnvValue -Path $dockerEnvPath -Name 'DOCKER_DB_HOST_PORT'

    Write-Host ''
    Write-Host 'MCARE is ready.' -ForegroundColor Green
    Write-Host "Application: http://127.0.0.1:$appPort"
    Write-Host "Shared login: http://127.0.0.1:$appPort/login"
    Write-Host "Docker database: 127.0.0.1:$databasePort (mcare_docker_dev only)"
    if ($WithVite) {
        Write-Host "Vite: http://127.0.0.1:$vitePort"
    } else {
        Write-Host 'Vite dev server is stopped; pass -WithVite when live frontend updates are needed.'
    }

    Write-Host ''
    Write-Host 'Useful commands:'
    Write-Host '  docker compose --env-file .env.docker ps'
    Write-Host '  docker compose --env-file .env.docker logs -f app'
    Write-Host '  docker compose --env-file .env.docker exec app php artisan test'
    Write-Host '  docker stats --no-stream'
    Write-Host '  docker compose --env-file .env.docker stop'
} finally {
    Pop-Location
}
