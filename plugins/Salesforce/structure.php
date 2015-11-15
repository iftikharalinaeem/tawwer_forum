<?php
/**
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */
if (!defined('APPLICATION')) exit();

$Structure = Gdn::Structure();
$Structure
   ->Table('Attachment')
   ->PrimaryKey('AttachmentID')
   ->Column('Type', 'varchar(64)') // ex: salesforce-lead, salesforce-ticket
   ->Column('ForeignID', 'varchar(50)', FALSE, 'index') // ex: d-123 for DiscussionID 123, u-555 for UserID 555
   ->Column('ForeignUserID', 'int', FALSE, 'key') // the user id of the record we are attached to (de-normalization)
   ->Column('Source', 'varchar(64)') // ex: Salesforce
   ->Column('SourceID', 'varchar(32)') // ex: 500i0000007T43IAAS
   ->Column('SourceURL', 'varchar(255)') // ex: https://login.salesforce.com/500i0000007T43IAAS
   ->Column('Attributes', 'text', TRUE)
   ->Column('DateInserted', 'datetime')
   ->Column('InsertUserID', 'int', FALSE, 'key')
   ->Column('InsertIPAddress', 'varchar(64)')
   ->Column('DateUpdated', 'datetime', TRUE)
   ->Column('UpdateUserID', 'int', TRUE)
   ->Column('UpdateIPAddress', 'ipaddress', TRUE)
   ->Set(FALSE, FALSE);
