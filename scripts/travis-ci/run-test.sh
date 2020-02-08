#!/bin/bash

# Run either PHPUnit tests or PHP_CodeSniffer tests on Travis CI, depending
# on the passed in parameter.

mysql_to_ramdisk() {
  echo " > Move MySQL datadir to RAM disk."
  sudo service mysql stop
  sudo mv /var/lib/mysql /var/run/tmpfs
  sudo ln -s /var/run/tmpfs /var/lib/mysql
  sudo service mysql start
}

TEST_DIRS=($DRUPAL_DIR/modules/og/tests $DRUPAL_DIR/modules/og/og_ui/tests)

case "$1" in
    PHP_CodeSniffer)
        cd $MODULE_DIR
        composer install
        ./vendor/bin/phpcs
        exit $?
        ;;
    *)
        mysql_to_ramdisk
        ln -s $MODULE_DIR $DRUPAL_DIR/modules/og
        cd $DRUPAL_DIR
        EXIT=0
        for i in ${TEST_DIRS[@]}; do
          echo " > Executing tests from $i"
          ./vendor/bin/phpunit -c ./core/phpunit.xml.dist $i || EXIT=1
        done
        exit $EXIT
esac
