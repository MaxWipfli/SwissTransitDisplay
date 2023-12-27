FROM php:8.3.0-apache-bookworm

# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Copy files to webroot
COPY ./src /var/www/html

# Copy htaccess file to webroot, to disallow access to the config file (with the
# API secret key)
COPY ./docker/htaccess /var/www/html/.htaccess

# Alias config (which is mounted at /config.json) into the webroot
RUN ln -sf /var/www/html/config.json /config.json
