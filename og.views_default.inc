<?php
// $Id$

// Declare all the .view files in the views subdir that end in .view
function og_views_default_views() {
  $files = file_scan_directory(drupal_get_path('module', 'og'). '/views', '.view');
  foreach ($files as $absolute => $file) {
    require $absolute;
    $views[substr($file->name, 3)] = $view;
  }
  return $views;
}