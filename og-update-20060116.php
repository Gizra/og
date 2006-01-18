<?php
// $Id$

include_once "includes/bootstrap.inc";
drupal_bootstrap(DRUPAL_BOOTSTRAP_DATABASE);

$sql = "DELETE FROM {node_access} WHERE realm = 'og_uid'";
db_query($sql);

$sql = "SELECT nid FROM {node} WHERE type = 'og'";
$result = db_query($sql);
while ($row = db_fetch_object($result)) {
  $sql = "REPLACE INTO {node_access} (nid, gid, realm, grant_view, grant_update, grant_delete) VALUES (%d, %d, 'og_group', 1, 1, 1)";
  db_query($sql, $row->nid, $row->nid);
}

?>