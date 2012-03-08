#!/bin/bash

# Run this from the terminal inside a drupal root folder
# i.e. DRUPAL_ROOT_DIR/sites/all/modules/contrib/ctools/tests/ctools.drush.sh

function stamp {
  echo ==============
  echo timestamp : `date`
  echo ==============
}

$EXPORT_DIR=ctools_drush_test

stamp

echo 'Enabling views module.'
drush en views ctools --yes

stamp
echo 'Reading all export info'
drush ctools-export-info

stamp
echo 'Enabling all default views'
drush ctools-export-enable views_view --yes

stamp
echo 'Reading all enabled exportables'
drush ctools-export-info --show-disabled

stamp
echo 'Disable default "archive" view'
drush ctools-export-disable views_view archive

stamp
echo 'Reading all enabled exportables (archive disabled)'
drush ctools-export-info --show-disabled

stamp
echo 'Disabling all default views'
drush ctools-export-disable views_view --yes

stamp
echo 'Revert all default views'
drush ctools-export-revert views_view --yes

stamp
echo 'Bulk export all objects'
drush ctools-export $EXPORT_DIR --subdir='/tmp/'

stamp
echo 'Show all files in created folder'
ls -lAR /tmp/$EXPORT_DIR

stamp
echo 'Removing exported object files'
rm -Rf /tmp/$EXPORT_DIR
