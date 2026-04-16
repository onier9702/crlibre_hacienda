FROM php:8.2-apache

LABEL version="1.2"

RUN apt-get update && apt-get -y install \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    curl \
    libcurl4-openssl-dev \
    openssl \
    netcat-openbsd \
    dos2unix \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mysqli gd curl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

# openssl.cnf path
COPY docker-php-apache/openssl.cnf /usr/lib/ssl/openssl.cnf

# Force OpenSSL to use it
ENV OPENSSL_CONF=/usr/lib/ssl/openssl.cnf

COPY ./docker-php-apache/docker-entrypoint.sh /usr/local/bin/
RUN dos2unix /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

COPY ./www/ /var/www/html
COPY ./api/ /var/www/api

RUN mkdir -p /var/www/api/errors \
    /var/www/api/logs \
    /var/www/api/files

RUN chmod -R 755 /var/www/ \
    && chmod -R 777 /var/www/api/errors \
    && chmod -R 777 /var/www/api/logs \
    && chmod -R 777 /var/www/api/files

RUN ln -s /usr/local/bin/docker-entrypoint.sh /

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
