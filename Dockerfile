FROM php:8.3-cli

# Install system dependencies for Composer, if needed
RUN apt-get update && apt-get install -y \
    git unzip curl libzip-dev && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /usr/src/app

# Copy only the necessary files to install dependencies
COPY composer.json composer.lock ./
COPY cyw ./cyw

RUN composer install

# Copy the rest of the app (run.php, getImages.php, etc.)
COPY . .

# Set working directory
WORKDIR /usr/src/app