#!/usr/bin/env bash

phpcs --standard=Drupal -p --extensions=php,module,inc,install,test,profile,theme,js,css,info .

if [ $? -ne 0 ]; then
  # If there was an error try to fix it, and re-run PHPCS.

  echo
  echo "$Autofixing errors, and re-running PHPCS"
  echo

  phpcbf --standard=Drupal -p --colors --extensions=php,module,inc,install,test,profile,theme,js,css,info .
  phpcs --standard=Drupal -p --extensions=php,module,inc,install,test,profile,theme,js,css,info .
fi
