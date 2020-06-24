<?php if (!defined('APPLICATION')) exit;
      
$St = Gdn::structure();
$Sql = Gdn::sql();
$Database = Gdn::database();

/* Body should be a serialized array of entity attributes. Examples: 
 * An image would contain: path, filesize, dimensions. 
 * A link would contain: url, all fetchpageinfo properties.

$St->table('Entity')
   ->primaryKey('EntityID')
   ->column('TypeID', 'int', TRUE)
   ->column('ForeignID', 'int(11)', TRUE)
   ->column('ForeignTable', 'varchar(24)', TRUE)
   ->column('Body', 'text', FALSE)
   ->column('DateInserted', 'datetime')
   ->column('InsertUserID', 'int', FALSE, 'key')
   ->column('DateUpdated', 'datetime', TRUE)
   ->column('UpdateUserID', 'int', TRUE)
   ->set();

$St->table('EntityType')
   ->primaryKey('EntityTypeID')
   ->column('Name', 'varchar(50)', TRUE)
   ->set();
 */
// Define permissions
$PermissionModel = Gdn::permissionModel();
$PermissionModel->Database = $Database;
$PermissionModel->SQL = $Sql;

// Define some permissions for the Polls categories.
$PermissionModel->define([
	'Plugins.Images.Add' => 'Garden.Profiles.Edit'
]);
   
