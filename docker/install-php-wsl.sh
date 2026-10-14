#!/usr/bin/env bash
# Installs PHP + Composer natively in WSL (Ubuntu 26.04, which ships PHP 8.5).
#
# Optional: the project runs entirely in Docker without this. Install it if you
# want `php artisan` on the host, IDE tooling, or the laravel-boost MCP server
# (which needs a host PHP binary) to work.
#
# Run with:  sudo bash docker/install-php-wsl.sh
set -euo pipefail

if [[ $EUID -ne 0 ]]; then
    echo "Run with sudo: sudo bash docker/install-php-wsl.sh" >&2
    exit 1
fi

echo "==> Updating package lists"
apt-get update

echo "==> Installing PHP and the extensions this project needs"
# Ubuntu 26.04's default PHP is 8.5; Laravel 13 requires ^8.3, so this is fine.
# Unversioned package names track the distro default.
apt-get install -y --no-install-recommends \
    php-cli \
    php-pgsql \
    php-redis \
    php-intl \
    php-zip \
    php-gd \
    php-mbstring \
    php-xml \
    php-curl \
    php-bcmath \
    unzip \
    git \
    make

echo "==> Installing Composer"
curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
EXPECTED="$(curl -fsSL https://composer.github.io/installer.sig)"
ACTUAL="$(php -r 'echo hash_file("sha384", "/tmp/composer-setup.php");')"
if [[ "$EXPECTED" != "$ACTUAL" ]]; then
    echo "Composer installer checksum mismatch; aborting." >&2
    rm -f /tmp/composer-setup.php
    exit 1
fi
php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm -f /tmp/composer-setup.php

echo
echo "==> Done"
php -v | head -2
composer --version
echo
echo "PHP extensions:"
php -m | tr '\n' ' '
echo
echo
echo "Notes:"
echo " - The app still runs in Docker. Host PHP is for tooling and the MCP server."
echo " - Host .env DB settings point at the containers; to use artisan from the"
echo "   host against your Windows Postgres, set DB_HOST=127.0.0.1 DB_PORT=5432."
