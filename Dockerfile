FROM drupal:8.1-apache

# Install Composer.
RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer

# Install Drush
RUN export PATH="$HOME/.composer/vendor/bin:$PATH" \
		&& composer global require drush/drush:8.*

# drush si --db-url=mysql://root:root@172.17.0.2/drupal -y

EXPOSE 80 3306 22
