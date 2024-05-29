# Dockerfile
ARG php_version=8.3
FROM php:${php_version}-cli

# Install dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    mariadb-client \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql

# Install xdebug
RUN yes | pecl install xdebug \
    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.mode = debug" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && rm -rf /tmp/pear

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set the working directory
WORKDIR /app

# Keep the container running by default
CMD ["/bin/sh"]
