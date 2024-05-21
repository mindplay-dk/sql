#!/bin/bash

# Ensure the script exits on any error
set -e

# Start the Docker services if not already running
docker compose up -d

# Run the test suite within the already running php container, passing the environment variables
docker compose exec php composer run test
