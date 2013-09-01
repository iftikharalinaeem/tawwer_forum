<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 */

// Define the plugin:
$PluginInfo['Reporting2'] = array(
   'Name' => 'Reporting',
   'Description' => 'Allows users to report posts to moderators for abuse, terms of service violations etc.',
   'Version' => '2.0a',
   'RequiredApplications' => array('Vanilla' => '2.1'),
   'SettingsUrl' => '/settings/reporting',
   'SettingsPermission' => 'Garden.Users.Manage',
   'Author' => "Todd Burry",
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class Reporting2Plugin extends Gdn_Plugin {
   /// Methods ///
   public function Setup()  {
      $this->Structure();
   }
   
   public function Structure() {
      Gdn::Structure()->Table('Category')
         ->Column('Type', 'varchar(20)')
         ->Set();
      
      // Try and find the category by type.
      $CategoryModel = new CategoryModel();
      $Category = $CategoryModel->GetWhereCache(array('Type' => 'Reporting'));
      if (empty($Category)) {
         $Row = array(
            'Name' => 'Reported Posts',
            'UrlCode' => 'reported-posts',
            'HideAllDiscussions' => 1,
            'DisplayAs' => 'Discussions',
            'Type' => 'Reporting',
            'AllowDiscussions' => 1,
            'Sort' => 1000);
         $ID = $CategoryModel->Save($Row);
      }
   }
   
   /// Event Handlers ///
}

if (!function_exists('FormatQuote')):

function FormatQuote($Body) {
   if (is_object($Body)) {
      $Body = (array)$Body;
   } elseif (is_string($Body)) {
      return $Body;
   }
   
   $User = Gdn::UserModel()->GetID(GetValue('InsertUserID', $Body));
   if ($User) {
      $Result = '<blockquote class="Quote Media">'.
         '<div class="Img">'.UserPhoto($User).'</div>'.
         '<div class="Media-Body">'.
            '<div>'.UserAnchor($User).'</div>'.
            Gdn_Format::To($Body['Body'], $Body['Format']).
         '</div>';
         '</blocquote>';
   } else {
      $Result = '<blockquote class="Quote">'.
         Gdn_Format::To($Body['Body'], $Body['Format']);
         '</blocquote>';
   }
   
   return $Result;
}
   
endif;

if (!function_exists('Quote')):

function Quote($Body) {
   return FormatQuote($Body);
}
   
endif;