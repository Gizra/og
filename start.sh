#!/usr/bin/env bash

# Get the current travis branch.
set branch_name $env(PULL_REQUEST_BRANCH)

# State MySQL service.
service mysql start

# Install Drush.
export PATH="$HOME/.composer/vendor/bin:$PATH"
composer global require drush/drush:7.*

# Install Drupal.
drush si standard --account-name=admin --account-pass=admin --db-url=mysql://root@127.0.0.1/drupal -y

# Install the module.
cd sites/all/modules/
git clone https://github.com/Gizra/og.git --branch $branch_name --depth 1
drush en -y og

# Run the tests
drush en -y simpletest
cd cd -
php ./scripts/run-tests.sh --php $(which php) --concurrency 4 --verbose --color --url http://localhost "Organic groups","Organic groups access","Organic groups context","Organic groups field access","Organic groups UI" 2>&1 | tee /tmp/simpletest-result.txt
egrep -i "([1-9]+ fail)|(Fatal error)|([1-9]+ exception)" /tmp/simpletest-result.txt && exit 1
exit 0
