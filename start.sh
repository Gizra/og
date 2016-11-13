#!/usr/bin/env bash

# State MySQL service.
service mysql start

# Install Drush.
export PATH="$HOME/.composer/vendor/bin:$PATH"
composer global require drush/drush:7.*

# Install Drupal.
drush si standard --account-name=admin --account-pass=admin --db-url=mysql://root@127.0.0.1/drupal -y

# Install the module.
cd sites/all/modules/
git clone https://github.com/Gizra/og.git --branch $TRAVIS_PULL_REQUEST_BRANCH --depth 1
drush en -y og

# Run the tests.
# ...
