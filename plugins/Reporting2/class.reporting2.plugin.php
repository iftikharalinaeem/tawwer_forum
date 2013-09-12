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

      // Turn off Flagging & Reporting plugins (upgrade)
      RemoveFromConfig('EnabledPlugins.Flagging');
      RemoveFromConfig('EnabledPlugins.Reporting');
   }

   /// Controller ///

   /**
    * Handles report actions.
    *
    * @param $Sender
    * @param $RecordType
    * @param $ReportType
    * @param $ID
    * @throws Gdn_UserException
    */
   public function RootController_Report_Create($Sender, $RecordType, $ReportType, $ID) {
      if (!Gdn::Session()->IsValid())
         throw new Gdn_UserException(T('You need to sign in before you can do this.'), 403);

      $Sender->Form = new Gdn_Form();
      $Sender->SetData('Title', sprintf(T('Report %1s %2s'), $ReportType, $RecordType));

      

      $Sender->Render('report', '', 'plugins/Reporting2');
   }
   
   /// Event Handlers ///

   /**
    * Make sure Reactions' flags are triggered, but remove Spam if present.
    */
   public function Base_BeforeFlag_Handler($Sender, $Args) {
      if (empty($Args['Flags']))
         $Args['Flags'] = TRUE;
      elseif (isset($Args['Flags']['spam']))
         unset($Args['Flags']['spam']);
   }

   /**
    * Add reporting options to discussions & comments under Flag menu.
    */
   public function Base_AfterFlagOptions_Handler($Sender, $Args) {
      $Options = array('Spam', 'Inappropriate');
      foreach ($Options as $Name) {
         $Text = Sprite('React'.$Name, 'ReactSprite').' '.Wrap(T($Name), 'span', array('class' => 'ReactLabel'));
         echo Wrap(Anchor($Text, 'report/'.$Args['RecordType'].'/'.strtolower($Name).'/'.$Args['RecordID'],
            'Popup ReactButton ReactButton-'.$Name, array('title'=>$Name, 'rel'=>"nofollow")), 'li');
      }
   }
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