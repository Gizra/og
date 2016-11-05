#!/usr/bin/env bash

# State MySQL service.
service mysql start

# Install Drush.
export PATH="$HOME/.composer/vendor/bin:$PATH"
composer global require drush/drush:7.*

# Install Drupal.
drush si standard --account-name=admin --account-pass=admin --db-url=mysql://root@127.0.0.1/drupal -y

# Install the module.
# ...

# Run the tests.
# ...
