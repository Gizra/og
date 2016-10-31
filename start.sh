#!/usr/bin/env bash

echo "[start] Start MySQL server"
service mysql start

echo "[start] Install Drupal"
drush si standard --account-name=admin --account-pass=admin --db-url=mysql://root@127.0.0.1/drupal -y

echo "[start] Install the module"
# ...

echo "[start] Run the tests"
# ...
