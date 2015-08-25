#!/bin/bash

echo "running drupal_ti/before/before_script.sh"

set -e $DRUPAL_TI_DEBUG

# Ensure the right Drupal version is installed.
# Note: This function is re-entrant.
drupal_ti_ensure_drupal

echo "installed drupal"

# Change to the Drupal Directory
cd "$DRUPAL_TI_DRUPAL_DIR"

# Manually clone the dependencies
git clone https://github.com/d8-contrib-modules/encrypt.git modules/encrypt
git clone https://github.com/d8-contrib-modules/key.git modules/key
