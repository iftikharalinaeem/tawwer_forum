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
   'Author' => "Tim Gunter, Mark O'Sullivan",
   'AuthorEmail' => 'support@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class StatisticsPlugin extends Gdn_Plugin {
   
   const RESOLUTION_HOUR = 'hour';
   const RESOLUTION_DAY = 'day';
   const RESOLUTION_WEEK = 'week';
   const RESOLUTION_MONTH = 'month';
   
   const FILL_ZERO = 'zero';
   const FILL_NULL = 'null';
   
   // Record a pageview if loading a full page.
   public function Base_Render_Before($Sender) {
      if ($Sender->DeliveryType() == DELIVERY_TYPE_ALL)
         $this->TrackEvent('pageviews');
   }
   
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
      set_time_limit(0);
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
      self::TrackItem($RealType, $Qualifier, $Date, self::RESOLUTION_WEEK);
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
         case self::RESOLUTION_WEEK:
            $DateStart = date('Y-m-d',strtotime('last sunday',$DateRaw));
            $DateEnd = date('Y-m-d',strtotime('this saturday',$DateRaw));
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
   
   public static function GetDataRange($Type, $Qualifier, $Resolution, $RangeStart, $RangeEnd, $FillMode = self::FILL_ZERO) {
      $RangeStartRaw = (!is_int($RangeStart)) ? strtotime($RangeStart) : $RangeStart;
      if ($RangeStartRaw === FALSE)
         throw new Exception("Invalid range start date '{$RangeStart}' used when attempting to get data for '{$Type}:{$Qualifier}'");
         
      $RangeEndRaw = (!is_int($RangeEnd)) ? strtotime($RangeEnd) : $RangeEnd;
      if ($RangeEndRaw === FALSE)
         throw new Exception("Invalid range end date '{$RangeEnd}' used when attempting to get data for '{$Type}:{$Qualifier}'");
         
      switch ($Resolution) {
         case self::RESOLUTION_HOUR:
            $DateStart = date('Y-m-d H:00:00',$RangeStartRaw);
            $DateEnd = date('Y-m-d H:00:00',$RangeEndRaw);
            break;
         case self::RESOLUTION_WEEK:
            $DateStart = date('Y-m-d',strtotime('last sunday',$RangeStartRaw));
            $DateEnd = date('Y-m-d',strtotime('this saturday',$RangeEndRaw));
            break;
         case self::RESOLUTION_DAY:
            $DateStart = date('Y-m-d',$RangeStartRaw);
            $DateEnd = date('Y-m-d',$RangeEndRaw);
            break;
         case self::RESOLUTION_MONTH:
            $DateStart = date('Y-m-01',$RangeStartRaw);
            $DateEnd = date('Y-m-t',$RangeEndRaw);
            break;
         default:
            throw new Exception("Invalid range resolution '{$Resolution}' used when attempting to track '{$Type}:{$Qualifier}'");
      }
      
      $NullValue = ($FillMode == self::FILL_ZERO) ? 0 : NULL;
      
      $StatQuery = Gdn::SQL()
         ->Select('s.DateRangeStart')
         ->Select('s.IndexValue')
         ->From('Statistics s')
         ->Where('DateRangeType', $Resolution)
         ->Where('IndexType', $Type)
         ->Where('DateRangeStart >=', $DateStart)
         ->Where('DateRangeEnd <=', $DateEnd)
         ->OrderBy('s.DateRangeStart', 'asc');
         
      if (!is_null($Qualifier))
         $StatQuery->Where('IndexQualifier', $Qualifier);
      
      $StatData = $StatQuery->Get();
      
      $StatResults = array();
      if ($StatData->NumRows()) {
         $DateInterval = NULL;
         $DateExpect = $DateStart; $DateLast = NULL;
         while ($Stat = $StatData->NextRow()) {
            $DateInterval = self::DateFormatByResolution($Stat->DateRangeStart, $Resolution);
            
            // The date I'm reading in is not what I expected. Need to create some fake data.
            if ($DateInterval != $DateExpect && $DateInterval != $DateLast) {
               $WorkingDate = $DateExpect;
               do {
                  
                  if (!array_key_exists($WorkingDate, $StatResults))
                     $StatResults[$WorkingDate] = array(
                        'Date'      => $WorkingDate,
                        'Value'     => $NullValue
                     );
                  
                  $WorkingDate = self::NextDate($WorkingDate, $Resolution);
                  $Continue = ($WorkingDate < $DateInterval);
               } while ($Continue);
            }
            
            if (!array_key_exists($DateInterval, $StatResults))
               $StatResults[$DateInterval] = array(
                  'Date'            => $DateInterval,
                  'Value'           => $Stat->IndexValue
               );
            else
               $StatResults[$DateInterval]['Value'] += $Stat->IndexValue;
            
            $DateNextInterval = self::NextDate($Stat->DateRangeStart, $Resolution);
            $DateExpect = $DateNextInterval;
            $DateLast = $DateInterval;
         }
      }
      
      if (!sizeof($StatResults) || $DateInterval < $DateEnd) {
         $WorkingDate = (sizeof($StatResults)) ? $DateInterval : $DateStart;
         do {
            
            if (!array_key_exists($WorkingDate, $StatResults))
               $StatResults[$WorkingDate] = array(
                  'Date'      => $WorkingDate,
                  'Value'     => $NullValue
               );
            
            $WorkingDate = self::NextDate($WorkingDate, $Resolution);
            $Continue = ($WorkingDate <= $DateEnd);
         } while ($Continue);
      }
      return $StatResults;
   }
   
   protected static function NextDate($CurrentDate, $Resolution) {
      $DateRaw = (!is_int($CurrentDate)) ? strtotime($CurrentDate) : $CurrentDate;
      if ($DateRaw === FALSE)
         throw new Exception("Invalid range start date '{$CurrentDate}' while calculating next date");
      
      $NextDateRaw = strtotime("+1 {$Resolution}", $DateRaw);
      return self::DateFormatByResolution($NextDateRaw, $Resolution);
   }
   
   protected static function DateFormatByResolution($Date, $Resolution) {
   
      $DateRaw = (!is_int($Date)) ? strtotime($Date) : $Date;
      if ($DateRaw === FALSE)
         throw new Exception("Invalid date '{$Date}', unable to convert to epoch");
      
      switch ($Resolution) {
         case self::RESOLUTION_HOUR:
            return date('Y-m-d H:00:00',$DateRaw);
         case self::RESOLUTION_WEEK:
            return date('Y-m-d',strtotime('last sunday',$DateRaw));
         case self::RESOLUTION_DAY:
            return date('Y-m-d',$DateRaw);
         case self::RESOLUTION_MONTH:
            return date('Y-m-01',$DateRaw);
            
         default:
            throw new Exception("Invalid date resolution '{$Resolution}'");
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
   
   /**
    * Override the default index method of the settings controller in the
    * dashboard application to render new statistics.
    */
   public function SettingsController_Index_Create($Sender) {
      // Load javascript & css, check permissions, and load side menu for this page.
      $Sender->AddJsFile('settings.js');
      $Sender->AddJsFile('plugins/Statistics/js/raphael.js');
      $Sender->AddJsFile('plugins/Statistics/js/graph.js');
      $Sender->AddJsFile('plugins/Statistics/js/picker.js');
      $Sender->AddCSSFile('plugins/Statistics/design/graph.css');
      $Sender->AddCSSFile('plugins/Statistics/design/picker.css');
      $Sender->Title(T('Dashboard'));
      $Sender->RequiredAdminPermissions[] = 'Garden.Settings.Manage';
      $Sender->RequiredAdminPermissions[] = 'Garden.Routes.Manage';
      $Sender->RequiredAdminPermissions[] = 'Garden.Applications.Manage';
      $Sender->RequiredAdminPermissions[] = 'Garden.Plugins.Manage';
      $Sender->RequiredAdminPermissions[] = 'Garden.Themes.Manage';
      $Sender->RequiredAdminPermissions[] = 'Garden.Registration.Manage';
      $Sender->RequiredAdminPermissions[] = 'Garden.Applicants.Manage';
      $Sender->RequiredAdminPermissions[] = 'Garden.Roles.Manage';
      $Sender->RequiredAdminPermissions[] = 'Garden.Users.Add';
      $Sender->RequiredAdminPermissions[] = 'Garden.Users.Edit';
      $Sender->RequiredAdminPermissions[] = 'Garden.Users.Delete';
      $Sender->RequiredAdminPermissions[] = 'Garden.Users.Approve';
      $Sender->FireEvent('DefineAdminPermissions');
      $Sender->Permission($Sender->RequiredAdminPermissions, '', FALSE);
      $Sender->AddSideMenu('dashboard/settings');
      
      $this->ConfigureRange($Sender);
      
      // Render the custom dashboard view
      $Sender->Render(PATH_PLUGINS.'/Statistics/views/dashboard.php');
   }
   
   public function SettingsController_DashboardSummaries_Create($Sender) {
      // Load javascript & css, check permissions, and load side menu for this page.
      $Sender->AddJsFile('settings.js');
      $Sender->Title(T('Dashboard Summaries'));
      $Sender->RequiredAdminPermissions[] = 'Garden.Settings.Manage';
      $Sender->RequiredAdminPermissions[] = 'Garden.Routes.Manage';
      $Sender->RequiredAdminPermissions[] = 'Garden.Applications.Manage';
      $Sender->RequiredAdminPermissions[] = 'Garden.Plugins.Manage';
      $Sender->RequiredAdminPermissions[] = 'Garden.Themes.Manage';
      $Sender->RequiredAdminPermissions[] = 'Garden.Registration.Manage';
      $Sender->RequiredAdminPermissions[] = 'Garden.Applicants.Manage';
      $Sender->RequiredAdminPermissions[] = 'Garden.Roles.Manage';
      $Sender->RequiredAdminPermissions[] = 'Garden.Users.Add';
      $Sender->RequiredAdminPermissions[] = 'Garden.Users.Edit';
      $Sender->RequiredAdminPermissions[] = 'Garden.Users.Delete';
      $Sender->RequiredAdminPermissions[] = 'Garden.Users.Approve';
      $Sender->FireEvent('DefineAdminPermissions');
      $Sender->Permission($Sender->RequiredAdminPermissions, '', FALSE);
      $Sender->AddSideMenu('dashboard/settings');
      
      $this->ConfigureRange($Sender);

      $UserModel = new UserModel();
      $Sender->SetData('DiscussionData', $UserModel->SQL
         ->Select('d.DiscussionID, d.Name, d.CountBookmarks, d.CountViews, d.CountComments')
         ->From('Discussion d')
         ->Where('d.DateLastComment >=', $Sender->DateStart)
         ->Where('d.DateLastComment <=', $Sender->DateEnd)
         ->OrderBy('d.CountComments', 'desc')
         ->OrderBy('d.CountViews', 'desc')
         ->OrderBy('d.CountBookmarks', 'desc')
         ->Limit(10, 0)
         ->Get()
      );
      
      $Sender->SetData('UserData', $UserModel->SQL
         ->Select('u.UserID, u.Name')
         ->Select('c.CommentID', 'count', 'CountComments')
         ->From('User u')
         ->Join('Comment c', 'u.UserID = c.InsertUserID', 'inner')
         ->GroupBy('u.UserID, u.Name')
         ->Where('c.DateInserted >=', $Sender->DateStart)
         ->Where('c.DateInserted <=', $Sender->DateEnd)
         ->OrderBy('CountComments', 'desc')
         ->Limit(10, 0)
         ->Get()
      );
      
      // Render the custom dashboard view
      $Sender->Render(PATH_PLUGINS.'/Statistics/views/dashboardsummaries.php');
   }

   private function ConfigureRange($Sender) {
      // Grab the range resolution from the url or form. Default to "day" range.
      $Sender->Range = GetIncomingValue('Range');
      if (!in_array($Sender->Range, array(
            StatisticsPlugin::RESOLUTION_HOUR,
            StatisticsPlugin::RESOLUTION_DAY,
            StatisticsPlugin::RESOLUTION_WEEK,
            StatisticsPlugin::RESOLUTION_MONTH)))
         $Sender->Range = StatisticsPlugin::RESOLUTION_DAY;
         
      // Define default values for start & end dates
      $Sender->HourStampStart = strtotime('-24 hours');
      $Sender->DayStampStart = strtotime('-1 month'); // Default to 1 month ago
      $Sender->WeekStampStart = strtotime('-12 weeks'); // Default to 24 weeks ago
      $Sender->MonthStampStart = strtotime('-12 months'); // Default to 24 months ago
      $Sender->HourDateStart = Gdn_Format::ToDate($Sender->HourStampStart);
      $Sender->DayDateStart = Gdn_Format::ToDate($Sender->DayStampStart);
      $Sender->WeekDateStart = Gdn_Format::ToDate($Sender->WeekStampStart);
      $Sender->MonthDateStart = Gdn_Format::ToDate($Sender->MonthStampStart);
      
      // Validate that any values coming from the url or form are valid
      $Sender->DateRange = GetIncomingValue('DateRange');
      $DateRangeParts = explode('-', $Sender->DateRange);
      $Sender->StampStart = strtotime(GetValue(0, $DateRangeParts));
      $Sender->StampEnd = strtotime(GetValue(1, $DateRangeParts));
      if (!$Sender->StampEnd)
         $Sender->StampEnd = time();
         
      // If no date was provided, or the provided values were invalid, use defaults
      if (!$Sender->StampStart) {
         $Sender->StampEnd = time();
         if ($Sender->Range == 'day') $Sender->StampStart = $Sender->DayStampStart;
         if ($Sender->Range == 'week') $Sender->StampStart = $Sender->WeekStampStart;
         if ($Sender->Range == 'month') $Sender->StampStart = $Sender->MonthStampStart;
      }
      
      // Assign the variables used in the page with the validated values.
      $Sender->DateStart = Gdn_Format::ToDate($Sender->StampStart);
      $Sender->DateEnd = Gdn_Format::ToDate($Sender->StampEnd);
      $Sender->DateRange = $Sender->DateStart . ' - ' . $Sender->DateEnd;

      // Define the range boundaries.
      $Database = Gdn::Database();
      $Data = $Database->SQL()->Select('DateRangeStart')->From('Statistics')->WhereNotIn('DateRangeStart', array('1975-09-01','1975-09-16'))->OrderBy('DateRangeStart', 'asc')->Limit(1)->Get()->FirstRow();
      $Sender->BoundaryStart = Gdn_Format::Date($Data ? $Data->DateRangeStart : $Sender->DateStart, '%Y-%m-%d');
      $Data = $Database->SQL()->Select('DateRangeEnd')->From('Statistics')->OrderBy('DateRangeEnd', 'desc')->Limit(1)->Get()->FirstRow();
      $Sender->BoundaryEnd = Gdn_Format::Date($Data ? $Data->DateRangeEnd : $Sender->DateEnd, '%Y-%m-%d');
   }
   
   private function GetData($Sender) {
      // Retrieve associated data for graph
      $UserData = StatisticsPlugin::GetDataRange('registrations', NULL, $Sender->Range, $Sender->DateStart, $Sender->DateEnd);
      $CommentData = StatisticsPlugin::GetDataRange('comments', NULL, $Sender->Range, $Sender->DateStart, $Sender->DateEnd);
      $DiscussionData = StatisticsPlugin::GetDataRange('discussions', NULL, $Sender->Range, $Sender->DateStart, $Sender->DateEnd);
      $PageViewData = StatisticsPlugin::GetDataRange('pageviews', NULL, $Sender->Range, $Sender->DateStart, $Sender->DateEnd);

      // Build a single array that contains all of the data
      $Data = array(
         'Dates' => array(),
         'Page Views' => array(),
         'Users' => array(),
         'Discussions' => array(),
         'Comments' => array()
      );
      foreach ($UserData as $Date => $Value) {
         $Data['Dates'][] = date(date('Y', Gdn_Format::ToTimestamp($Date)) < date('Y') ? 'M j, Y' : 'M j', strtotime($Date));
         $Data['Page Views'][] = $PageViewData[$Date]['Value'];
         $Data['Users'][] = $Value['Value'];
         $Data['Discussions'][] = $DiscussionData[$Date]['Value'];
         $Data['Comments'][] = $CommentData[$Date]['Value'];
      }
      return $Data;
   }
   
   public function SettingsController_LoadStats_Create($Sender) {
      $this->ConfigureRange($Sender);
      echo json_encode($this->GetData($Sender));
      // Make sure the database connection is closed before exiting.
      Gdn::Database()->CloseConnection();
      exit();
   }
   
   /**
    * Get the default starting date for the specified range resolution.
    * @param object $Sender The controller being attached to.
    * @param string $Range The range resolution to get the default start date for.
    */
   public static function GetDateStart($Sender, $Range) {
      if ($Range == StatisticsPlugin::RESOLUTION_HOUR)
         return $Sender->HourDateStart;
      else if ($Range == StatisticsPlugin::RESOLUTION_DAY)
         return $Sender->DayDateStart;
      else if ($Range == StatisticsPlugin::RESOLUTION_WEEK)
         return $Sender->WeekDateStart;
      else if ($Range == StatisticsPlugin::RESOLUTION_MONTH) {
         return $Sender->MonthDateStart;
      } else {
         return $Sender->DateStart;
      }
   }
   
   public function Setup() {
      $this->Structure();
      $this->_RegisterTrackedItem(array(
         'pageviews'       => 'none',
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