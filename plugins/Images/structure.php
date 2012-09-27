<?php if (!defined('APPLICATION')) exit;
      
$St = Gdn::Structure();
$Sql = Gdn::SQL();
$Database = Gdn::Database();

/* Body should be a serialized array of entity attributes. Examples: 
 * An image would contain: path, filesize, dimensions. 
 * A link would contain: url, all fetchpageinfo properties.

$St->Table('Entity')
   ->PrimaryKey('EntityID')
   ->Column('TypeID', 'int', TRUE)
   ->Column('ForeignID', 'int(11)', TRUE)
   ->Column('ForeignTable', 'varchar(24)', TRUE)
   ->Column('Body', 'text', FALSE)
   ->Column('DateInserted', 'datetime')
   ->Column('InsertUserID', 'int', FALSE, 'key')
   ->Column('DateUpdated', 'datetime', TRUE)
   ->Column('UpdateUserID', 'int', TRUE)
   ->Set();

$St->Table('EntityType')
   ->PrimaryKey('EntityTypeID')
   ->Column('Name', 'varchar(50)', TRUE)
   ->Set();
 */
// Define permissions
$PermissionModel = Gdn::PermissionModel();
$PermissionModel->Database = $Database;
$PermissionModel->SQL = $Sql;

// Define some permissions for the Polls categories.
$PermissionModel->Define(array(
	'Plugins.Images.Add' => 'Garden.Profiles.Edit'
));
   
