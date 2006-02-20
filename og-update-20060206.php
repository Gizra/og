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

// feb 19, 2006
// add a row for each combination of public node and group. needed to make public nodes show up in group homepage for non subscribers
$sql = "SELECT DISTINCT(nid) as nid FROM {node_access} WHERE realm = 'og_group' AND gid = 0" ;
$result = db_queryd($sql);
while ($row = db_fetch_object($result)) {
  $sql = "SELECT gid FROM {node_access} WHERE nid = %d AND realm = 'og_group' AND gid != 0" ;
  $result2 = db_queryd($sql, $row->nid);
  while ($row2 = db_fetch_object($result2)) {  
    $sql = "REPLACE INTO {node_access} (nid, realm, gid, grant_view) VALUE (%d, 'og_public', 0, %d)";
    db_queryd($sql, $row->nid, $row2->gid); 
  }
}

// change all former public node grants to 'og_all' realm
$sql = "UPDATE {node_access} SET realm = 'og_all' WHERE realm = 'og_group' AND gid = 0 AND grant_view = 1";
db_queryd($sql);

// change all nodes in groups to new 'og_subscriber' realm
$sql = "UPDATE {node_access} SET realm = 'og_subscriber' WHERE realm = 'og_group' AND gid != 0";
db_queryd($sql);

// these records are no longer used. we've migrated them to new grant scheme
$sql = "DELETE FROM {node_access} WHERE realm = 'og_group'";
db_queryd($sql);
