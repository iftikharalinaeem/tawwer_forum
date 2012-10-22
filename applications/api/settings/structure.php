<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

// Use this file to construct tables and views necessary for your application.
// There are some examples below to get you started.

if (!isset($Drop))
   $Drop = FALSE;
   
if (!isset($Explicit))
   $Explicit = TRUE;

$SQL = Gdn::SQL();
$St = Gdn::Structure();

$ApplicationTableExists = $St->TableExists('Application');

// Registered applications that are connected to the VPI
$St->Table('Application')
   ->PrimaryKey('ApplicationID', 'uint') // used for client_id
   ->Column('Name', 'varchar(200)')
   ->Column('Url', 'varchar(255)')
   ->Column('Secret', 'varchar(32)') // used for client_secret
   ->Column('DateInserted', 'datetime')
   ->Column('DateUpdated', 'datetime', TRUE)
   ->Column('InsertUserID', 'int')
   ->Column('UpdateUserID', 'int', NULL)
   ->Set($Explicit, $Drop);

// Make sure a default application is in to make the numbers correct.
$AppID = 12345678;
if (!$ApplicationTableExists || $SQL->GetCount('Application', array('ApplicationID' => $AppID)) == 0) {
   $Secret = RandomString(32, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');
   $SQL->History(FALSE, TRUE)
      ->Insert(
         'Application',
         array('ApplicationID' => $AppID, 'Name' => 'vanillaforums.com', 'Url' => '*', 'Secret' => $Secret));
}

// All of the applications that users have authenticated against.
$St->Table('UserApplication')
   ->Column('UserID', 'int', FALSE, 'primary')
   ->Column('ApplicationID', 'int', FALSE, 'primary')
   ->Column('SiteID', 'int', NULL, 'primary')
   ->Set($Explicit, $Drop);

// This is the table to connect tokens to users.
$St->Table('OAuth2Token')
   ->Column('Token', 'varchar(32)', FALSE, 'primary')
   ->Column('UserID', 'int', FALSE)
   ->Column('TokenType', array('authorization_code', 'access_token'), FALSE)
   ->Column('Timestamp', 'timestamp')
   ->Column('ExpiresIn', 'usmallint', '0')
   ->Set($Explicit, $Drop);

$St->Table('User')
   ->Column('DefaultAuthentication', 'varchar(64)', NULL)
   ->Set(FALSE, FALSE);

// Save the Facebook authentication provider.
Gdn::SQL()->Replace('UserAuthenticationProvider',
   array('AuthenticationSchemeAlias' => 'facebook', 'URL' => '...', 'AssociationSecret' => '...', 'AssociationHashMethod' => '...'),
   array('AuthenticationKey' => 'Facebook'));

