<?php if (!defined('APPLICATION')) exit();
/**
 * @package Localization
 */
 
/**
 * @since 1.0
 * @package Localization
 */
class LocalizationController extends VanillaController {
   /// Constants ///
   const PAGE_SIZE = 20;


   /// Properties ///
   
   /**
    *
    * @var LocalizationModel 
    */
   public $LocalizationModel;
   
   /// Methods ///
   
   public function Initialize() {
      parent::Initialize();
      $this->AddCssFile('localization.css');
      $this->AddJsFile('localization.js', '');
      $this->SetData('Breadcrumbs', array(array('Name' => T('Localization'), 'Url' => '/localization')));
      $this->LocalizationModel = new LocalizationModel();
   }
   
   public function Index() {
      $this->SetData('Locales', LocalizationModel::Locales());
      
      $this->FetchView('helper_functions');
      $this->Title(T('Help Translate Vanilla'), '');
      $this->Description(T('Help the Vanilla community localize Vanilla into your own language.'));
      $this->Render();
   }
   
   public function Approve($Locale, $Page = FALSE) {
      $this->Permission('Localization.Locales.Edit');
      $Locales = LocalizationModel::Locales();
      $LocaleInfo = GetValue($Locale, $Locales);
      
      if (!$Locale)
         throw NotFoundException('Locale');
      
      $this->_Save($LocaleInfo);
      $this->_Table($LocaleInfo, 'Approve', $Page);
      
      $this->AddDefinition('Language', substr($Locale, 0, 2));
      
      $this->Data['Breadcrumbs'][] = array('Name' => $LocaleInfo['Name'], 'Url' => "/localization/locale/$Locale");
      $this->Data['Breadcrumbs'][] = array('Name' => T('Approve Translations'), 'Url' => "/localizations/approve/$Locale");
      $this->Title(sprintf(T('Approve %s Translations'), $LocaleInfo['Name']));
      $this->FetchView('helper_functions');
      
      $this->Render();
   }
   
   public function Browse($Locale, $Page = FALSE) {
      $this->Permission('Localization.Locales.Edit');
      $Locales = LocalizationModel::Locales();
      $LocaleInfo = GetValue($Locale, $Locales);
      
      if (!$Locale)
         throw NotFoundException('Locale');
      
      $this->_Table($LocaleInfo, '', $Page);
      
      $this->AddDefinition('Language', substr($Locale, 0, 2));
      
      $this->Data['Breadcrumbs'][] = array('Name' => $LocaleInfo['Name'], 'Url' => "/localization/locale/$Locale");
      $this->Data['Breadcrumbs'][] = array('Name' => T('Browse Translations'), 'Url' => "/localization/browse/$Locale");
      $this->Title(sprintf(T('Browse %s Translations'), $LocaleInfo['Name']));
      $this->SetData('_UsePager', TRUE);
      
      $this->FetchView('helper_functions');
      $this->Render();
   }
   
   public function JoinTeam($Locale, $Join = TRUE, $TK = '') {
      $LocaleInfo = GetValue($Locale, LocalizationModel::Locales());
      if (!$LocaleInfo)
         throw NotFoundException('Locale');
      
      if (!Gdn::Session()->ValidateTransientKey($TK))
         throw PermissionException();
      $this->Permission('Localization.Locales.Edit');
      
      $UserID = Gdn::Session()->UserID;
      $Row = Gdn::SQL()->GetWhere('LocaleUser', array('Locale' => $Locale, 'UserID' => $UserID))->FirstRow(DATASET_TYPE_ARRAY);
      
      if ($Join) {
         if (!$Row) {
            Gdn::SQL()->Insert('LocaleUser', array('Locale' => $Locale, 'UserID' => $UserID, 'DateInserted' => Gdn_Format::ToDateTime()));
            
            // Add an activity for the event.
            $ActivityModel = new ActivityModel();
            $ActivityModel->Save(array(
                'ActivityType' => 'Team',
                'ActivityUserID' => $UserID,
                'NotifyUserID' => ActivityModel::NOTIFY_PUBLIC,
                'HeadlineFormat' => '{ActivityUserID,You} joined the <a href="{Url,html}">{Data.Name,html}</a> translation team.',
                'RecordType' => 'Locale',
                'RecordID' => $LocaleInfo['LocaleID'],
                'Route' => "/localization/locale/$Locale",
                'Data' => array('Name' => $LocaleInfo['Name'])
               ),
               FALSE,
               array('GroupBy' => array('ActivityTypeID', 'RecordID', 'RecordType'))
               );
            
         } elseif ($Row['Deleted']) {
            Gdn::SQL()->Put('LocaleUser', array('Deleted' => 0), array('Locale' => $Locale, 'UserID' => $UserID));
         }
      } else {
         if ($Row)
            Gdn::SQL()->Put('LocaleUser', array('Deleted' => 1), array('Locale' => $Locale, 'UserID' => $UserID));
      }
      Redirect("/localization/locale/$Locale");
   }
   
   public function Locale($Locale) {
      $Locales = LocalizationModel::Locales();
      $LocaleInfo = GetValue($Locale, $Locales);
      
      if (!$LocaleInfo)
         throw NotFoundException('Locale');
      
      $this->SetData('Locale', $LocaleInfo);
      
      $Team = Gdn::SQL()->GetWhere('LocaleUser', array('Locale' => $Locale, 'Deleted' => 0))->ResultArray();
      Gdn::UserModel()->JoinUsers($Team, array('UserID'));
      $this->SetData('Team', $Team);
      
      $this->Data['Breadcrumbs'][] = array('Name' => $LocaleInfo['Name'], 'Url' => $this->CanonicalUrl());
      $this->Title($this->Data('Locale.Name'));
      $this->Render();
   }
   
   public function _Save($Locale) {
      if (!isset($this->Form)) {
         $this->Form = new Gdn_Form();
         $this->Form->InputPrefix = '';
      }
      
      $Model = $this->LocalizationModel;
      
      if ($this->Form->IsPostBack()) {
         // Save the current translation.
         if ($this->Form->GetFormValue('Approve')) {
            $Model->Approve($Locale['Locale'], $this->Form->FormValues());
            $this->SetData('Action', 'Approve');
         } elseif ($this->Form->GetFormValue('Reject')) {
            $Model->Approve($Locale['Locale'], $this->Form->FormValues());
            $this->SetData('Action', 'Reject');
         } else {
            $this->SetData('Action', 'Save');
            
            $Model->SaveTranslation($Locale['Locale'], $this->Form->FormValues());
            $this->Form->SetValidationResults($Model->ValidationResults());
         }
         
         $Translation = $Model->SQL->GetWhere('LocaleTranslation', array('CodeID' => $this->Form->GetFormValue('CodeID'), 'Locale' => $Locale['Locale']))->FirstRow(DATASET_TYPE_ARRAY);
         $this->SetData('Translation', $Translation);
         if ($this->DeliveryType() == DELIVERY_TYPE_DATA) {
               // Don't do the big query if we are ajaxing data back.
               $this->Render();
               return;
            }
      }
   }
   
   public function SaveFilter($Target) {
      $this->Permission('Localization.Locales.Edit');
      
      $this->Form = new Gdn_Form();
      
      if ($this->Form->IsPostBack()) {
         $Filter = $this->Form->FormValues();
         Gdn::Session()->SetPreference('Localization.Filter', $Filter);
         Redirect($Target);
      }
   }
   
   protected function _SetFilter(&$Where) {
      $Filter = Gdn::Session()->GetPreference('Localization.Filter');
      if (!is_array($Filter))
         $Filter = array();
      
      TouchValue('Core', $Filter, TRUE);
      TouchValue('Admin', $Filter, TRUE);
      TouchValue('Addon', $Filter, TRUE);
      
      $CountGroup = array();
      if ($Filter['Core'])
         $CountGroup[] = 'Core';
      if ($Filter['Admin'])
         $CountGroup[] = 'Admin';
      if ($Filter['Addon'])
         $CountGroup = 'Addon';
      
      if (empty($CountGroup))
         $CountGroup[] = 'Core';
      
      $Where['CountGroup'] = $CountGroup;
   }
   
   public function Translate($Locale, $Page = FALSE) {
      $Locales = LocalizationModel::Locales();
      $LocaleInfo = GetValue($Locale, $Locales);
      $this->Form = new Gdn_Form();
      $this->Form->InputPrefix = '';
      
      if (!$LocaleInfo)
         throw NotFoundException('Locale');
      
      $this->_Save($LocaleInfo);
      $this->_Table($LocaleInfo, 'Translate', $Page);
      
      $this->AddDefinition('Language', substr($Locale, 0, 2));
      $this->AddDefinition('Filter', 'Translate');
      
      $this->Data['Breadcrumbs'][] = array('Name' => $LocaleInfo['Name'], 'Url' => "/localization/locale/$Locale");
      $this->Data['Breadcrumbs'][] = array('Name' => T('Translate'), 'Url' => $this->CanonicalUrl());
      $this->Title(sprintf(T('Translate Into %s'), $LocaleInfo['Name']));
      $this->FetchView('helper_functions');
      $this->Render();
   }
   
   protected function _Table($Locale, $Filter, $Page) {
      list($Offset, $Limit) = OffsetLimit($Page, self::PAGE_SIZE);
      $Page = PageNumber($Offset, $Limit);
      
      $Model = $this->LocalizationModel;
      
      $Where = array('Locale' => $Locale['Locale'], 'Status' => 'Active');
      
      // Set the user preference filter.
      $this->_SetFilter($Where);
      
      // Override with the argument filter.
      if (is_string($Filter)) {
         $this->AddDefinition('Filter', $Filter);
         switch ($Filter) {
            case 'Translate':
               $Filter = array('Approved' => array('New', 'Rejected'));
               break;
            case 'Approve':
               $Filter = array('Approved' => array('Translated'));
               break;
         }
      }
      
      // Add the filter from request.
      if ($this->Request->Get('Search')) {
         $Where['Search'] = $this->Request->Get('Search');
         $Where['Field'] = $this->Request->Get('Field');
      }
      
      if (is_array($Filter)) {
         $Where = array_merge($Where, $Filter);
      }
      
      $Translations = $this->LocalizationModel->GetTranslationsWhere($Where, $Offset, $Limit);
      $this->SetData('Translations', $Translations);
      
      $this->SetData('_Page', $Page);
      $this->SetData('Locale', $Locale);
      $this->AddDefinition('Locale', $Locale['Locale']);
   }
   
   public function Table($Locale, $Filter = '', $Page = FALSE) {
      $Locales = LocalizationModel::Locales();
      $Locale = GetValue($Locale, $Locales);
      
      if (!$Locale)
         throw NotFoundException('Locale');
      
      $this->_Table($Locale, $Filter, $Page);
      $this->Render('TranslationTable');
   }
}
