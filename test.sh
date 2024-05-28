#!/bin/bash

# Ensure the script exits on any error
set -e

# If a PHP version is passed as an argument, rebuild with the new PHP version
if [ -n "$1" ]; then
  docker compose down
  docker compose build --build-arg php_version=$1
fi

# Start the Docker services if not already running
docker compose up -d

# Run the test suite within the already running php container, passing the environment variables
docker compose exec php composer run test
