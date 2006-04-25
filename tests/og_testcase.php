<?php

class OgTestCase extends DrupalTestCase {
  function addOg($selective = OG_OPEN) {
    $edit = array();
    $edit['title']    = '!SimpleTest test group node! ' . $this->randomName(10);
    $edit['og_description'] = '!SimpleTest og description' . $this->randomName(10);
    $edit['body']     = '!SimpleTest test group welcome message! ' . $this->randomName(32) . ' ' . $this->randomName(32);
    $edit['og_selective'] = (string)$selective;

    $url = url('node/add/og', NULL, NULL, TRUE);
    $ret = $this->_browser->get($url);
    $this->assertTrue($ret, " [browser] GET $url");
    foreach ($edit as $field_name => $field_value) {
      $ret = $this->_browser->setFieldByName("edit[$field_name]", $field_value);
      $this->assertTrue($ret, " [browser] Setting edit[$field_name]=\"$field_value\"");
    }
    $this->_browser->setFieldByName('edit[og_theme]', ''); // May not be present, so no error catching
    
    $ret = $this->_browser->clickSubmit(t('Submit'));
//    $ret = $this->_browser->clickSubmitByName('op');
    $this->assertTrue($ret, ' [browser] POST by click on ' . t('Submit'));
    $this->_content = $this->_browser->getContent();

    $this->assertWantedText(t('Your %post was created.', array ('%post' => 'group')), 'Group created');

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