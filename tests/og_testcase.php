<?php

class OgTestCase extends DrupalTestCase {
  var $_cleanupGroups;
  
  function get_info() {
    return array();
  }
  
  function addOg($type, $selective = OG_OPEN) {
    
    $edit = array();
    $edit['title']          = '!SimpleTest test group node! ' . $this->randomName(10);
    $edit['og_description'] = '!SimpleTest og description' . $this->randomName(10);
    $edit['body']           = '!SimpleTest test group welcome message! ' . $this->randomName(32) . ' ' . $this->randomName(32);
    $edit['og_selective'] = (string)$selective;
//    $this->_browser->setFieldByName('edit[og_theme]', ''); // May not be present, so no error catching

    $this->drupalPost("node/add/$type", $edit, t('Submit'));

    $this->assertWantedRaw(t('Your %post has been created.', array ('%post' => $type)), 'Group created');

    $node = node_load(array('title' => $edit['title']));
    $this->assertNotNull($node, 'Group found in database. %s');
    $this->_cleanupGroups[] = $node->nid;
    
    return $node->nid;
  }
  
  // TODO: in D6, there is similar method in drupalTestCase
  function addNodeType() {
    
    $type = new stdClass();

    $name = strtolower($this->randomName());
    $type->type = trim($name);
    $type->name = trim($name);
    $type->orig_type = trim("");
    $type->old_type = $type->type;
  
    $type->description    = $this->randomName(32, "description ... ");
    $type->help           = $type->description = $this->randomName(32, "help ... ");
    $type->min_word_count = 0;
    $type->title_label    = "Title";
    $type->body_label     = "Body";
  
    $type->module         = 'node';
    $type->has_title      = $type->has_body = TRUE;
    $type->custom         = "";
    $type->modified       = TRUE;
    $type->locked         = TRUE;
  
    $status = node_type_save($type);
    $this->assertTrue(SAVED_NEW == $status, "Created node-type $name.");
    
    $this->_cleanupNodeTypes[] = $name;
    
    variable_set('og_content_type_usage_'. $name, 'group');
    
    return $name;
  }
  
  
  function tearDown() {
    while (sizeof($this->_cleanupGroups) > 0) {
      $gid = array_pop($this->_cleanupGroups);
      node_delete($gid);
    }
    
    include_once './'. drupal_get_path('module', 'node') .'/content_types.inc';
    while (sizeof($this->_cleanupNodeTypes) > 0) {
      $name = array_pop($this->_cleanupNodeTypes);

      $ar = array('name' => $name, 'type' => $name);
      // named this array so it can be passed by reference.
      // anonymous variables cannot.
      node_type_delete_confirm_submit(0, $ar);
    }
    parent::tearDown();
  }
}
