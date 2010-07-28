<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

// Define the plugin:
$PluginInfo['Statistics'] = array(
   'Name' => 'Statistics',
   'Description' => 'Gathers statistics about activity on your forum and lets you view historical stats.',
   'Version' => '0.5',
   'RequiredApplications' => FALSE,
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'HasLocale' => TRUE,
   'RegisterPermissions' => FALSE,
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class StatisticsPlugin extends Gdn_Plugin {
   
   const RESOLUTION_HOUR = 'hour';
   const RESOLUTION_DAY = 'day';
   const RESOLUTION_MONTH = 'month';
   
   public function Base_GetAppSettingsMenuItems_Handler(&$Sender) {
      $LinkText = T('Statistics');
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddItem('Forum', T('Forum'));
      $Menu->AddLink('Forum', $LinkText, 'plugin/statistics', 'Garden.Settings.Manage');
   }
   
   public function PluginController_Statistics_Create(&$Sender) {
      $Sender->Permission('Garden.Settings.Manage');
      $Sender->Title('Statistics');
      $Sender->AddSideMenu('plugin/statistics');
      $Sender->Form = new Gdn_Form();
      $this->Dispatch($Sender, $Sender->RequestArgs);
   }
   
   public function Controller_Index(&$Sender) {
      $Sender->AddCssFile('admin.css');
      $Sender->Render($this->GetView('statistics.php'));
   }
   
   public function Controller_Toggle(&$Sender) {
      // Enable/Disable Forum Statistics
      if (Gdn::Session()->ValidateTransientKey(GetValue(1, $Sender->RequestArgs))) {
         if (C('Plugins.Statistics.Enabled')) {
            $this->_Disable();
         } else {
            $this->_Enable();
         }
         Redirect('plugin/statistics');
      }
   }
   
   public function Controller_Catchup(&$Sender) {
      foreach (array('comments','discussions','registrations') as $TrackedItem) {
         $Method = 'Catchup'.ucfirst($TrackedItem);
         $this->$Method();
      }
      Redirect('plugin/statistics');
   }
   
   protected function _CatchupGeneric($Type, $TrackType) {

      // Figure out where to stop searching
      $EarliestData = Gdn::SQL()->Select('DateRangeStart', 'MIN', 'EarliestDate')
         ->From('Statistics')
         ->Where('DateRangeType', self::RESOLUTION_HOUR)
         ->Where('IndexType', $TrackType)
         ->Get();
         
      $EarliestDate = NULL;
      if ($EarliestData->NumRows()) {
         $EarliestData = $EarliestData->FirstRow(DATASET_TYPE_ARRAY);
         $EarliestDate = $EarliestData['EarliestDate'];
      } 
      if (is_null($EarliestDate))
         $EarliestDate = date('Y-m-d 00:00:01',strtotime('Tomorrow'));
      
      $Limit = 1000; $Offset = 0;
      do {
         $Items = Gdn::SQL()->Select('DateInserted')
            ->From($Type)
            ->Where('DateInserted<',$EarliestDate)
            ->Offset($Offset)
            ->Limit($Limit)
            ->Get();
         $NumItems = $Items->NumRows();
         $Offset += $NumItems;
         
         while ($Item = $Items->NextRow(DATASET_TYPE_ARRAY))
            $this->TrackEvent($TrackType, 'none', $Item['DateInserted']);
      } while ($NumItems);
   }
   
   protected function CatchupComments() {
      $this->_CatchupGeneric('Comment', 'comments');
   }
   
   protected function CatchupDiscussions() {
      $this->_CatchupGeneric('Discussion', 'discussions');
   }
   
   protected function CatchupRegistrations() {
      $this->_CatchupGeneric('User', 'registrations');
   }
   
   public function UserModel_AfterInsertUser_Handler(&$Sender) {
      $this->TrackEvent('registrations');
   }
   
   public function PostController_AfterDiscussionSave_Handler(&$Sender) {
      $this->TrackEvent('discussions');
   }
   
   public function PostController_AfterCommentSave_Handler(&$Sender) {
      $this->TrackEvent('comments');
   }
   
   protected function TrackEvent($RealType, $Qualifier = 'none', $Date = NULL) {
      if (!C('Plugins.Statistics.Enabled')) return;
      
      $Date = is_null($Date) ? time() : $Date;
      self::TrackItem($RealType, $Qualifier, $Date, self::RESOLUTION_HOUR);
      self::TrackItem($RealType, $Qualifier, $Date, self::RESOLUTION_DAY);
      self::TrackItem($RealType, $Qualifier, $Date, self::RESOLUTION_MONTH);
   }
   
   public static function Tracking($Type, $Qualifier = 'none', $Fresh = FALSE) {
      static $TrackedItems = NULL;
      
      if (is_null($TrackedItems) || $Fresh)
         $TrackedItems = C('Plugin.Statistics.Tracked');
         
      if (!array_key_exists($Type, $TrackedItems)) return FALSE;
      if ((is_array($TrackedItems[$Type]) && !in_array($Qualifier,$TrackedItems[$Type])) || $TrackedItems[$Type] != $Qualifier) return FALSE;
      return TRUE;
   }
   
   public static function TrackItem($Type, $Qualifier, $Datetime, $Range, $Amount = 1) {
      if (!self::Tracking($Type, $Qualifier))
         throw new Exception("Tried to track an event for unregistered Type:Qualifier combination '{$Type}:{$Qualifier}'");
      
      if (!is_int($Amount))
         throw new Exception("Tried to add non-integer tracking quantity '{$Amount}' to '{$Type}:{$Qualifier}'");
      
      $DateRaw = (!is_int($Datetime)) ? strtotime($Datetime) : $Datetime;
      if ($DateRaw === FALSE)
         throw new Exception("Invalid anchor date '{$Date}' used when attempting to track '{$Type}:{$Qualifier}'");
      
      switch ($Range) {
         case self::RESOLUTION_HOUR:
            $DateStart = date('Y-m-d H:00:00',$DateRaw);
            $DateEnd = date('Y-m-d H:00:00',$DateRaw);
            break;
         case self::RESOLUTION_DAY:
            $DateStart = date('Y-m-d',$DateRaw);
            $DateEnd = date('Y-m-d',$DateRaw);
            break;
         case self::RESOLUTION_MONTH:
            $DateStart = date('Y-m-01',$DateRaw);
            $DateEnd = date('Y-m-t',$DateRaw);
            break;
         default:
            throw new Exception("Invalid range resolution '{$Range}' used when attempting to track '{$Type}:{$Qualifier}'");
      }
      
      try {
         Gdn::Database()->SQL()->Insert('Statistics',array(
            'IndexType'       => $Type,
            'IndexQualifier'  => $Qualifier,
            'DateRangeStart'  => $DateStart,
            'DateRangeEnd'    => $DateEnd,
            'DateRangeType'   => $Range,
            'IndexValue'      => $Amount,
            'DateUpdated'     => date('Y-m-d H:i:s')
         ));
         
      } catch (Exception $e) {
         Gdn::Database()->SQL()->Update('Statistics')
            ->Set('IndexValue', 'IndexValue+'.$Amount, FALSE)
            ->Set('DateUpdated', date('Y-m-d H:i:s'))
            ->Where('DateRangeType', $Range)
            ->Where('IndexType', $Type)
            ->Where('IndexQualifier', $Qualifier)
            ->Where('DateRangeStart', $DateStart)
            ->Where('DateRangeEnd', $DateEnd)
            ->Put();
      }
   }
   
   public static function RegisterTrackedItem($TrackedItem, $Qualifiers = NULL) {
      if (!is_array($TrackedItem))
         $TrackedItem = array($TrackedItem => $Qualifiers);
         
      $CurrentlyTrackedItems = C('Plugin.Statistics.Tracked');
      foreach ($TrackedItem as $TrackedItemName => $TrackedQualifiers) {
         $CurrentlyTrackedItems[$TrackedItemName] = $TrackedQualifiers;
      }
      SaveToConfig('Plugin.Statistics.Tracked', $CurrentlyTrackedItems);
      self::Tracking(NULL, NULL, TRUE); // Refresh Tracking()'s internal static variable
   }
   
   public function _RegisterTrackedItem($TrackedItem, $Qualifiers = NULL) {
      self::RegisterTrackedItem($TrackedItem, $Qualifiers);
   }
   
   public function Setup() {
      $this->Structure();
      $this->_RegisterTrackedItem(array(
         'comments'        => 'none',
         'discussions'     => 'none',
         'registrations'   => 'none',
         'activity'        => array('guest','member')
      ));
   }
   
   public function Structure() {
      Gdn::Database()->Structure()->Table('Statistics')
         ->Engine('InnoDB')
         ->Column('DateRangeStart', 'datetime', FALSE, 'unique')
         ->Column('DateRangeEnd', 'datetime', FALSE, 'unique')
         ->Column('DateRangeType', array('hour','day','month'), FALSE, 'unique')
         ->Column('IndexType', 'varchar(32)', FALSE, 'unique')
         ->Column('IndexQualifier', 'varchar(32)', NULL, 'unique')
         ->Column('IndexValue', 'int', NULL)
         ->Column('DateUpdated', 'datetime', NULL)
         ->Set(FALSE, FALSE);
   }
   
   protected function _Enable() {
      SaveToConfig('Plugins.Statistics.Enabled', TRUE);
   }
   
   protected function _Disable() {
      RemoveFromConfig('Plugins.Statistics.Enabled');
   }
   
}