#!/bin/bash

# We need to install dependencies only for Docker
[[ ! -e /.dockerenv ]] && exit 0

set -xe

apt-get install -y libpq-dev libxml2-dev libxslt1-dev libpng-dev unoconv xpdf-utils imagemagick ghostscript \
&& docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
&& docker-php-ext-configure pdo_pgsql --with-pdo-pgsql \
&& docker-php-ext-install pdo_pgsql pgsql \
&& docker-php-ext-install xsl \
&& pecl install xdebug-2.9.3 \
&& docker-php-ext-enable xdebug \
&& docker-php-ext-install gd
