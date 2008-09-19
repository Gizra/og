<?php

// TODO: This  class is barely useful anymore.
class OgTestCase extends DrupalWebTestCase {
  var $_cleanupGroups;
  
  // function getInfo() {
  //     return array('name'  => t('Og testing functionality'),
  //                  'desc'  => t('Setup and teardown functionality for organic groups'),
  //                  'group' => 'Organic groups');
  //   }
  
  function addOg($type, $selective = OG_OPEN) {
    $edit = array();
    $title = '!SimpleTest test group node! ' . $this->randomName(10); 
    $edit['title']          = $title;
    $edit['og_description'] = '!SimpleTest og description' . $this->randomName(10);
    $edit['body']           = '!SimpleTest test group welcome message! ' . $this->randomName(32) . ' ' . $this->randomName(32);
    $edit['og_selective'] = (string)$selective;
    $node = $this->drupalCreateNode($edit);
    return $node->nid;
  }
}
