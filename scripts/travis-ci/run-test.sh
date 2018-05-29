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
    # Drupal console only works on Drupal 8.3.x.
    8.3.x)
        mysql_to_ramdisk
        ln -s $MODULE_DIR $DRUPAL_DIR/modules/og
        cd $DRUPAL_DIR
        ./vendor/bin/phpunit -c ./core/phpunit.xml.dist $MODULE_DIR/tests
        exit $?
        ;;
    *)
        mysql_to_ramdisk
        ln -s $MODULE_DIR $DRUPAL_DIR/modules/og
        cd $DRUPAL_DIR
        ./vendor/bin/phpunit -c ./core/phpunit.xml.dist --exclude-group=console $MODULE_DIR/tests
        exit $?
esac
