#!/bin/bash

# Run either PHPUnit tests or PHP_CodeSniffer tests on Travis CI, depending
# on the passed in parameter.

TEST_DIRS=($MODULE_DIR/tests $MODULE_DIR/og_ui/tests)

case "$1" in
    PHP_CodeSniffer)
        cd $MODULE_DIR
        composer install
        ./vendor/bin/phpcs
        exit $?
        ;;
    # Drupal console only works on Drupal 8.3.x.
    8.3.x)
        ln -s $MODULE_DIR $DRUPAL_DIR/modules/og
        cd $DRUPAL_DIR
        for i in ${TEST_DIRS[@]}; do
          ./vendor/bin/phpunit -c ./core/phpunit.xml.dist $i || exit 1
        done
        ;;
    *)
        ln -s $MODULE_DIR $DRUPAL_DIR/modules/og
        cd $DRUPAL_DIR
        for i in ${TEST_DIRS[@]}; do
          ./vendor/bin/phpunit -c ./core/phpunit.xml.dist --exclude-group=console $i || exit 1
        done
esac
