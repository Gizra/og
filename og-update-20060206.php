<?php
// $Id$

include_once "includes/bootstrap.inc";
drupal_bootstrap(DRUPAL_BOOTSTRAP_DATABASE);

$sql = "DELETE FROM {node_access} WHERE realm = 'og_uid'";
db_query($sql);

$sql = "SELECT DISTINCT(n.nid) FROM {node} n INNER JOIN {node_access} na ON n.nid = na.nid WHERE type != 'og' AND na.realm = 'og_group'";
$result = db_queryd($sql);
while ($row = db_fetch_object($result)) {
  $sql = "UPDATE {node_access} SET grant_view=1, grant_update=1, grant_delete=1 WHERE realm = 'og_group' AND nid = %d AND gid != 0";
  db_queryd($sql, $row->nid);
}

$sql = "SELECT nid FROM {node} WHERE type = 'og'";
$result = db_queryd($sql);
while ($row = db_fetch_object($result)) {
  $sql = "REPLACE INTO {node_access} (nid, gid, realm, grant_view, grant_update, grant_delete) VALUES (%d, %d, 'og_group', 1, 1, 0)";
  db_queryd($sql, $row->nid, $row->nid);
}

?>