#!/bin/bash

set -e $DRUPAL_TI_DEBUG

# Enable og_ui module.
cd "$DRUPAL_TI_DRUPAL_DIR"
drush -y en og_ui
