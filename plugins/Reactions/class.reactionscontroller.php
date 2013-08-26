<?php if (!defined('APPLICATION')) exit();
 
/**
 * Reactions controller
 * 
 * @since 1.0.0
 * @package Reputation
 */
class ReactionsController extends DashboardController {
   
   public function Initialize() {
      parent::Initialize();
      $this->Form = new Gdn_Form;
      $this->Application = 'dashboard';
   }
   
   /**
    * List reactions
    */
   public function Index() {
      $this->Permission('Garden.Settings.Manage');
      $this->Title(T('Reaction Types'));
      $this->AddSideMenu();
      
      // Grab all of the reaction types.
      $ReactionModel = new ReactionModel();
      $ReactionTypes = ReactionModel::GetReactionTypes();
      
      $this->SetData('ReactionTypes', $ReactionTypes);
      include_once $this->FetchViewLocation('settings_functions', '', 'plugins/Reactions');
      
      $this->Render('reactiontypes', '', 'plugins/Reactions');
   }
   
   /**
    * Get a reaction
    * 
    * @param string $UrlCode
    * @throws
    */
   public function Get($UrlCode) {
      $this->Permission('Garden.Settings.Manage');
      
      $Reaction = ReactionModel::ReactionTypes($UrlCode);
      if (!$Reaction)
         throw NotFoundException('reaction');
      
      $this->SetData('Reaction', $Reaction);
      
      $this->Render('blank', 'utility', 'dashboard');
   }
   
   /**
    * Add a reaction
    * 
    * Parameters:
    *  UrlCode
    *  Name
    *  Description
    *  Class
    *  Points
    * 
    */
   public function Add() {
      $this->Permission('Garden.Settings.Manage');
      $this->Title('Add Reaction');
      $this->AddSideMenu('reactions');
      
      $Rm = new ReactionModel();
      if ($this->Form->AuthenticatedPostBack()) {
         $Reaction = $this->Form->FormValues();
         $R = $Rm->DefineReactionType($Reaction);
         
         if ($R) {
            $this->SetData('Reaction', $Reaction);
            
            if ($this->DeliveryType() != DELIVERY_TYPE_DATA) {
               $this->InformMessage(FormatString(T("New reaction created"), $Reaction));
               Redirect('/reactions');
            }
         }
         
      }
      
      $this->Render('addedit', '', 'plugins/Reactions');
   }
   
   public function Undo($Type, $ID, $Reaction, $UserID) {
      $this->Permission(array('Garden.Moderation.Manage'), FALSE);
      include_once $this->FetchViewLocation('reaction_functions', '', 'plugins/Reactions');
      
      $ReactionModel = new ReactionModel();
      $ReactionModel->React($Type, $ID, 'Undo-'.$Reaction, $UserID);
      
      $this->JsonTarget('!parent', '', 'SlideUp');
      
      $this->Render('Blank', 'Utility', 'Dashboard');
   }
   
   /**
    * Edit a reaction
    * 
    * Parameters:
    *  UrlCode
    *  Name
    *  Description
    *  Class
    *  Points
    * 
    * @param string $UrlCode
    * @throws type
    */
   public function Edit($UrlCode) {
      $this->Permission('Garden.Settings.Manage');
      $this->Title('Edit Reaction');
      $this->AddSideMenu('reactions');
      
      $Rm = new ReactionModel();
      $Reaction = ReactionModel::ReactionTypes($UrlCode);
      if (!$Reaction)
         throw NotFoundException('reaction');
      
      $this->SetData('Reaction', $Reaction);
      $this->Form->SetData($Reaction);
      
      if ($this->Form->AuthenticatedPostBack()) {
         $ReactionData = $this->Form->FormValues();
         $ReactionData = array_merge($Reaction, $ReactionData);
         $ReactionID = $Rm->DefineReactionType($ReactionData);
         
         if ($ReactionID) {
            $Reaction['ReactionID'] = $ReactionID;
            $this->SetData('Reaction', $ReactionData);
            
            if ($this->DeliveryType() != DELIVERY_TYPE_DATA) {
               $this->InformMessage(FormatString(T("New reaction created"), $ReactionData));
               Redirect('/reactions');
            }
         }
         
      }
      
      $this->Render('addedit', '', 'plugins/Reactions');
   }
   
   public function Log($Type, $ID) {
      $this->Permission(array('Garden.Moderation.Manage', 'Moderation.Reactions.Edit'), FALSE);
      $Type = ucfirst($Type);
      
      $ReactionModel = new ReactionModel();
      list($Row, $Model) = $ReactionModel->GetRow($Type, $ID);
      if (!$Row)
         throw NotFoundException(ucfirst($Type));
      
      $ReactionModel->JoinUserTags($Row, $Type);
      
      TouchValue('UserTags', $Row, array());
      Gdn::UserModel()->JoinUsers($Row['UserTags'], array('UserID'));
      
      $this->Data = $Row;
      $this->SetData('RecordType', $Type);
      $this->SetData('RecordID', $ID);
      
      $this->Render('log', 'reactions', 'plugins/Reactions');
   }
   
   public function RecalculateRecordCache($Day = FALSE) {
      $this->Permission('Garden.Settings.Manage');
      
      if (!$this->Request->IsAuthenticatedPostBack())
         throw ForbiddenException('GET');
      
      $ReactionModel = new ReactionModel();
      $Count = $ReactionModel->RecalculateRecordCache($Day);
      $this->SetData('Count', $Count);
      $this->SetData('Success', TRUE);
      $this->Render();
   }
   
   /**
    * Toggle a given reaction on or off
    * 
    * @param string $UrlCode
    * @param boolean $Active 
    */
   public function Toggle($UrlCode, $Active) {
      $this->Permission('Garden.Settings.Manage');
      
      $this->Form->InputPrefix = '';
      if (!$this->Form->AuthenticatedPostBack()) {
         throw PermissionException('PostBack');
      }
      
      $ReactionType = ReactionModel::ReactionTypes($UrlCode);
      if (!$ReactionType)
         throw NotFoundException('Reaction Type');
      
      $ReactionModel = new ReactionModel();
      $Reaction = ReactionModel::ReactionTypes($UrlCode);
      $ReactionType['Active'] = $Active;
      $Set = ArrayTranslate($ReactionType, array('UrlCode', 'Active'));
      $ReactionModel->DefineReactionType($Set);
      
      $Reaction = array_merge($Reaction, $Set);
      $this->SetData('Reaction', $Reaction);
      
      if ($this->DeliveryType() != DELIVERY_TYPE_DATA) {
         // Send back the new button.
         include_once $this->FetchViewLocation('settings_functions', '', 'plugins/Reactions');
         $this->DeliveryType(DELIVERY_METHOD_JSON);

         $this->JsonTarget("#ReactionType_{$ReactionType['UrlCode']} .ActivateSlider", ActivateButton($ReactionType), 'ReplaceWith');

         $this->JsonTarget("#ReactionType_{$ReactionType['UrlCode']}", 'InActive', $ReactionType['Active'] ? 'RemoveClass' : 'AddClass');      
      }
      
      $this->Render('blank', 'utility', 'dashboard');
   }
   
   public function Advanced() {
      $this->Permission('Garden.Settings.Manage');
      
      $Conf = new ConfigurationModule($this);
      $Conf->Initialize(array(
          'Plugins.Reactions.ShowUserReactions' => array('LabelCode' => 'Show who reacted below posts.', 'Control' => 'CheckBox', 'Default' => 1),
          'Plugins.Reactions.BestOfStyle' => array('LabelCode' => 'Best of Style', 'Control' => 'RadioList', 'Items' => array('Tiles' => 'Tiles', 'List' => 'List'), 'Default' => 'Tiles'),
          'Plugins.Reactions.DefaultOrderBy' => array('LabelCode' => 'Order Comments By', 'Control' => 'RadioList', 'Items' => array('DateInserted' => 'Date', 'Score' => 'Score'), 'Default' => 'DateInserted',
              'Description' => 'You can order your comments based on reactions. We recommend ordering the comments by date.'),
          'Plugins.Reactions.DefaultEmbedOrderBy' => array('LabelCode' => 'Order Embedded Comments By', 'Control' => 'RadioList', 'Items' => array('DateInserted' => 'Date', 'Score' => 'Score'), 'Default' => 'Score',
              'Description' => 'Ordering your embedded comments by reaction will show just the best comments. Then users can head into the community to see the full discussion.')
      ));
      
      $this->Title(sprintf(T('%s Settings'), 'Reaction'));
      $this->AddSideMenu('reactions');
      $Conf->RenderAll();
   }
}