<?php

class OgTestCase extends DrupalTestCase {
  var $_cleanupGroups;
  
  function get_info() {
    return array('name'  => t('Og testing functionality'),
                 'desc'  => t('Setup and teardown functionality for organic groups'),
                 'group' => 'Organic groups');
  }
  
  function addOg($type, $selective = OG_OPEN) {
    $edit = array();
    $title = '!SimpleTest test group node! ' . $this->randomName(10); 
    $edit['title']          = $title;
    $edit['og_description'] = '!SimpleTest og description' . $this->randomName(10);
    $edit['body']           = '!SimpleTest test group welcome message! ' . $this->randomName(32) . ' ' . $this->randomName(32);
    $edit['og_selective'] = (string)$selective;
    $this->drupalPost("node/add/".str_replace('_', '-', $type), $edit, t('Save'));
    $this->assertWantedRaw(t('has been created', array ('%post' => $type, '%title' => $title)), 'Group created'); //TODO: make accurate again
    $node = node_load(array('title' => $edit['title']));
    $this->assertNotNull($node, 'Group found in database. %s');
    $this->_cleanupGroups[] = $node->nid;
    return $node->nid;
  }
  
  function tearDown() {
    while (sizeof($this->_cleanupGroups) > 0) {
      $gid = array_pop($this->_cleanupGroups);
      node_delete($gid);
    }
    
    parent::tearDown();
  }
}
