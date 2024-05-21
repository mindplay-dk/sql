# Dockerfile
FROM php:8.1-cli

# Install dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    mariadb-client \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy the current directory contents into the container
COPY . /app

# Set the working directory
WORKDIR /app

# Install PHP dependencies
RUN composer update

# Keep the container running by default
CMD ["/bin/sh"]
