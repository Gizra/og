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
        cp $DRUPAL_DIR/modules/og/phpunit.xml.dist ./core/phpunit.xml.dist
        ./vendor/bin/phpunit --coverage-clover "$TRAVIS_BUILD_DIR/build/logs/clover.xml" -c ./core/phpunit.xml.dist || EXIT=1
        exit $?
esac
