#!/bin/bash

# Ensure the script exits on any error
set -e

# PHP version can be specified from the CLI
PHP_VERSION=$1

# If a PHP version is passed as an argument, rebuild with the new PHP version
if [ -n "$PHP_VERSION" ]; then
  docker compose down
  docker compose build --build-arg php_version=$PHP_VERSION
fi

# Start the Docker services if not already running
docker compose up -d

# Install the composer dependencies within the already running PHP container
if [ -n "$PHP_VERSION" ]; then
  docker compose exec php rm -rf composer.lock vendor
  docker compose exec php composer update --no-interaction --prefer-dist
fi

# Run the test suite within the already running php container, passing the environment variables
docker compose exec php composer run test
docker compose exec php composer exec phpstan
