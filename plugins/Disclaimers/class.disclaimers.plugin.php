<?php if (!defined('APPLICATION')) exit();
/*
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

$PluginInfo['Disclaimers'] = array(
   'Name' => 'Disclaimers',
   'Description' => 'Adds disclaimers to user-defined categories.',
   'Version' => '1.0b',
   'Author' => "Todd Burry",
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.org/profile/todd',
   'RequiredApplications' => array('Vanilla' => '2.0.18b'),
   'SettingsUrl' => '/dashboard/settings/disclaimers',
   'SettingsPermission' => 'Garden.Settings.Manage',
);

class DisclaimersPlugin extends Gdn_Plugin {
   /// Properties ///

   /// Methods ///
   
   public static function CategoryIDs() {
      static $CategoryIDs = NULL;
      if ($CategoryIDs === NULL)
         $CategoryIDs = explode(',', C('Plugins.Disclaimers.CategoryIDs'));

      return $CategoryIDs;
   }

   public function RedirectDisclaim($Category) {
      $CategoryID = GetValue('CategoryID', $Category);
      if ($CategoryID && in_array($CategoryID, self::CategoryIDs())) {
         if (!Gdn::Session()->GetPreference('Disclaimed_'.$CategoryID)) {
            Redirect('/entry/disclaimer/'.rawurlencode(GetValue('UrlCode', $Category)));
         }
      }
   }

   /// Event Handlers ///

   public function CategoriesController_Render_Before($Sender, $Args) {
      $Category = $Sender->Data('Category');
      $this->RedirectDisclaim($Category);
   }

   public function DiscussionController_RendeR_Before($Sender, $Args) {
      $Category = CategoryModel::Categories($Sender->Data('CategoryID'));
      $this->RedirectDisclaim($Category);
   }

   /**
    *
    * @param Gdn_Controller $Sender
    * @param array $Args
    */
   public function EntryController_Disclaimer_Create($Sender, $Args) {
      if (count($Args) <= 0)
         throw NotFoundException();

      $Category = CategoryModel::Categories($Args[0]);
      if (!$Category)
         throw NotFoundException();

      $CategoryID = $Category['CategoryID'];
      $TK = $Sender->Request->Get('TK');
      $Disclaimed = $Sender->Request->Get('Disclaimed');

      $Target = $Sender->Request->Get('Target', '/categories/'.rawurlencode($Category['UrlCode']));


      $Form = new Gdn_Form();

      if (Gdn::Session()->ValidateTransientKey($TK)) {
         Gdn::Session()->SetPreference('Disclaimed_'.$CategoryID, $Disclaimed);
         if ($Disclaimed) {
            Redirect($Target);
         } else {
            Redirect('/');
         }
      }

      $Sender->SetData('Title', T('Warning'));
      $Sender->SetData('Disclaimer', T("Disclaimer.$CategoryID.Text", 'Some of the material in this section may not be suitable for all viewers. Please continue only if your are comfortable viewing such material.'));
      $Sender->SetData('TK', Gdn::Session()->TransientKey());
      $Sender->SetData('Target', $Target);
      $Sender->SetData('CategoryID', $Args[0]);

      $Sender->Render('Disclaimer', '', 'plugins/Disclaimers');
   }

   /**
    *
    * @param Gdn_Controller $Sender
    * @param array $Args
    */
   public function SettingsController_Disclaimers_Create($Sender, $Args) {
      $Sender->Permission('Garden.Settings.Manage');
      $Sender->AddSideMenu('');

      $Sender->Title(T('Disclaimer Settings'));

      if ($Sender->Form->AuthenticatedPostBack()) {
         if ($Sender->Form->ErrorCount() == 0) {
            $CategoryIDs = $Sender->Form->GetFormValue('CategoryIDs', array());
            if (is_array($CategoryIDs))
               $CategoryIDs = implode(',', $CategoryIDs);
            else
               $CategoryIDs = '';

            SaveToConfig(array(
                'Plugins.Disclaimers.CategoryIDs' => $CategoryIDs
            ));

            $Sender->InformMessage(T('Saved'));
         }
      } else {
         // Grab the settings from the config.

         $CategoryIDs = explode(',', C('Plugins.Disclaimers.CategoryIDs'));
         $Sender->Form->SetValue('CategoryIDs', $CategoryIDs);

      }

      $Categories = CategoryModel::Categories();
      $Categories = ConsolidateArrayValuesByKey($Categories, 'CategoryID', 'Name');
      unset($Categories[-1]);
      $Categories = array_flip($Categories);
      $Sender->SetData('_Categories', $Categories);

      $Sender->Render('Settings', '', 'plugins/Disclaimers');
   }

}
