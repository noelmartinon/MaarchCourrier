FROM phpdockerio/php56-fpm:latest

# Install selected extensions and other stuff
RUN apt-get update -y \
    && apt-get -y --no-install-recommends install  php5-pgsql php5-gd php5-pecl-http php5-imap php5-intl php5-radius php5-svn php5-xmlrpc php-pear php5-xsl \
    && apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

RUN apt-get install -y php-pear
RUN pear channel-discover pear.maarch.org
RUN pear install maarch/CLITools-0.3.1
RUN pear install SOAP-0.13.0

WORKDIR "/var/www/maarch"