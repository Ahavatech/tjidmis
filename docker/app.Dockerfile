# Local run of the journal app: PHP 8.1 + Apache with the extensions the app needs (pdo_mysql, gd, mbstring, fileinfo, dom).
FROM php:8.1-apache
RUN apt-get update && apt-get install -y --no-install-recommends libpng-dev libjpeg-dev libfreetype6-dev libxml2-dev libonig-dev \
 && docker-php-ext-configure gd --with-jpeg --with-freetype \
 && docker-php-ext-install pdo_mysql gd mbstring dom \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*
RUN { echo 'upload_max_filesize=32M'; echo 'post_max_size=40M'; echo 'memory_limit=256M'; echo 'date.timezone=Africa/Lagos'; } > /usr/local/etc/php/conf.d/journal.ini
RUN mkdir -p /var/journal_files && chown www-data:www-data /var/journal_files
WORKDIR /var/www/html
