<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['AdvancedSearch'] = array(
   'Name' => 'Advanced Search',
   'Description' => "Enables advanced search on sites.",
   'Version' => '1.0a',
   'MobileFriendly' => TRUE,
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class AdvancedSearchPlugin extends Gdn_Plugin {
   /// Methods ///
   
   public static function MassageSearch($Search) {
      $Search = array_change_key_case($Search);
      $Search = array_map(function($v) { return strtolower(trim($v)); }, $Search);
      $Search = array_filter($Search);
      TouchValue('dosearch', $Search, true);
      
      /// Author ///
      if (isset($Search['author'])) {
         $Usernames = explode(',', $Search['author']);
         $Usernames = array_map('trim', $Usernames);
         $Usernames = array_filter($Usernames);
         
         $Users = Gdn::SQL()->Select('UserID, Name')->From('User')->Where('Name', $Usernames)->Get()->ResultArray();
         if (count($Usernames) == 1 && empty($Users)) {
            // Searching for one author that doesn't exist.
            $Search['dosearch'] = FALSE;
         }
         
         if (!empty($Users))
            $Search['users'] = $Users;
      }
      
      /// Category ///
      $CategoryFilter = array();
      $Archived = GetValue('archived', $Search, 0);
      $CategoryID = GetValue('categoryid', $Search);
      
      if (!$CategoryID) {
         switch ($Archived) {
            case 1:
               // Include both, do nothing.
               break;
            case 2:
               // Only archive.
               $CategoryFilter['Archived'] = 1;
               break;
            case 0:
            default:
               // Not archived.
               $CategoryFilter['Archived'] = 0;
         }
      }
      $Categories = CategoryModel::GetByPermission('Discussions.View', NULL, $CategoryFilter);
      $Categories = array_keys($Categories);
      
      if ($CategoryID) {
         if (!in_array($CategoryID, $Categories)) {
            $Search['categoryid'] = FALSE;
            $Search['dosearch'] = FALSE;
         }
      } else {
         $Search['categoryid'] = $Categories;
      }
      
      /// Date ///
      if (isset($Search['date'])) {
         // Try setting the date.
         $Timestamp = strtotime($Search['date']);
         if ($Timestamp) {
            $Search['houroffset'] = Gdn::Session()->HourOffset();
            $Timestamp += -Gdn::Session()->HourOffset() * 3600;
            $Search['date'] = Gdn_Format::ToDateTime($Timestamp);
            
            if (isset($Search['within'])) {
               $Search['timestamp-from'] = strtotime('-'.$Search['within'], $Timestamp);
               $Search['timestamp-to'] = strtotime('+'.$Search['within'], $Timestamp);
            } else {
               $Search['timestamp-from'] = $Search['date'];
               $Search['timestamp-to'] = strtotime('+1 day', $Timestamp);
            }
            $Search['date-from'] = Gdn_Format::ToDateTime($Search['timestamp-from']);
            $Search['date-to'] = Gdn_Format::ToDateTime($Search['timestamp-to']);
         } else {
            unset($Search['date']);
         }
      } else {
         unset($Search['within']);
      }
      
      /// Tags ///
      if (isset($Search['tags'])) {
         $Tags = explode(',', $Search['tags']);
         $Tags = array_map('trim', $Tags);
         $Tags = array_filter($Tags);
         
         $TagData = Gdn::SQL()->Select('TagID, Name')->From('Tag')->Where('Name', $Tags)->Get()->ResultArray();
         if (count($Tags) == 1 && empty($TagData)) {
            // Searching for one author that doesn't exist.
            $Search['dosearch'] = FALSE;
            unset($Search['tags']);
         }
         
         if (!empty($TagData)) {
            $Search['tags'] = $TagData;
            TouchValue('tags-op', $Search, 'or');
         } else {
            unset($Search['tags'], $Search['tags-op']);
         }
         
      }
      Trace($Search, 'calc search');
      return $Search;
   }
   
   
   /// Event Handlers ///
   
   /**
    * @param AssetModel $Sender
    */
   public function AssetModel_StyleCss_Handler($Sender) {
      $Sender->AddCssFile('advanced-search.css', 'plugins/AdvancedSearch');
   }
   
   public function Base_Render_Before($Sender) {
      if (!InSection('Dashboard')) {
         AdvancedSearchModule::AddAssets();
      }
   }
   
   public function SearchController_AutoComplete_Create($sender, $term) {
      $searchModel = new SearchModel();
      $results = $searchModel->autoComplete($term);
      die(json_encode($results['SearchResults']));
   }
   
   /**
    * 
    * @param SearchController $Sender
    * @param type $Search
    * @param type $Page
    */
   public function SearchController_Index_Create($Sender, $Search = '', $Page = FALSE) {
      Gdn_Theme::Section('SearchResults');
      
      $this->Sender = $Sender;
      if ($Sender->Head) {
         // Don't index search results pages.
         $Sender->Head->AddTag('meta', array('name' => 'robots', 'content' => 'noindex'));
         $Sender->Head->AddTag('meta', array('name' => 'googlebot', 'content' => 'noindex'));
      }
      
      list($Offset, $Limit) = OffsetLimit($Page, C('Garden.Search.PerPage', 10));
      $Sender->SetData('_Offset', $Offset);
      $Sender->SetData('_Limit', $Limit);
      
      // Do the search.
      $SearchModel = new SearchModel();
      $Sender->SetData('SearchResults', array());
      $SearchTerms = Gdn_Format::Text($Search);
      
      if (method_exists($SearchModel, 'advancedSearch')) {
         $Results = $SearchModel->AdvancedSearch($Sender->Request->Get(), $Offset, $Limit);
         $Sender->SetData($Results);
         $SearchTerms = $Results['SearchTerms'];
      } else {
         $Results = $SearchModel->Search($Search, $Offset, $Limit);
         $Sender->SetData('SearchResults', $Results, TRUE);
         $SearchTerms = explode(' ', $SearchTerms);
      }
      $this->CalculateResults($Sender->Data['SearchResults'], $SearchTerms, !$Sender->Request->Get('nomark'));
      Gdn::UserModel()->JoinUsers($Sender->Data['SearchResults'], array('UserID'));
		$Sender->SetData('SearchTerm', implode(' ', $SearchTerms), TRUE);
      $Sender->SetData('SearchTerms', $SearchTerms, TRUE);
      $Sender->SetData('From', $Offset + 1);
      $Sender->SetData('To', $Offset + count($Sender->Data['SearchResults']));
      
//      $PagerFactory = new Gdn_PagerFactory();
//		$this->Pager = $PagerFactory->GetPager('MorePager', $this);
//		$this->Pager->MoreCode = 'More Results';
//		$this->Pager->LessCode = 'Previous Results';
//		$this->Pager->ClientID = 'Pager';
//		$this->Pager->Configure(
//			$Offset,
//			$Limit,
//			$NumResults,
//			'dashboard/search/%1$s/%2$s/?Search='.Gdn_Format::Url($Search)
//		);
      
      $this->Render('Search');
   }
   
   protected function CalculateResults(&$Data, $SearchTerms, $Mark = true) {
      if (!is_array($SearchTerms)) {
         $SearchTerms = explode(' ', $SearchTerms);
         $SearchTerms = array_filter($SearchTerms, function($v) { return trim($v);  });
      }
      
      $UseCategories = C('Vanilla.Categories.Use');
      $Breadcrumbs = array();
      
      foreach ($Data as &$Row) {
         $Row['Title'] = MarkString($SearchTerms, Gdn_Format::Text($Row['Title'], FALSE));
         $Row['Url'] = Url($Row['Url'], true);
         $Row['Score'] = (int)$Row['Score'];
         $Row['Body'] = $Row['Summary'];
         $Row['Summary'] = SearchExcerpt(Gdn_Format::PlainText($Row['Body'], $Row['Format']), $SearchTerms);
         
         // Add breadcrumbs for discussions.
         if ($UseCategories && isset($Row['CategoryID'])) {
            $CategoryID = $Row['CategoryID'];
            if (isset($Breadcrumbs[$CategoryID]))
               $Row['Breadcrumbs'] = $Breadcrumbs[$CategoryID];
            else {
               $Categories = CategoryModel::GetAncestors($CategoryID);
               $R = array();
               foreach ($Categories as $Cat) {
                  $R[] = array(
                     'Name' => $Cat['Name'],
                     'Url' => CategoryUrl($Cat)
                     );
               }
               $Row['Breadcrumbs'] = $R;
               $Breadcrumbs[$CategoryID] = $R;
            }
         }
      }
   }
}

if (!function_exists('SearchExcerpt')):
   
function SearchExcerpt($PlainText, $SearchTerms, $Length = 160, $Mark = true) {
   if (is_string($SearchTerms))
      $SearchTerms = preg_split('`[\s|-]+`i', $SearchTerms);
   
   // Split the string into lines.
   $Lines = explode("\n", $PlainText);
   // Find the first line that includes a search term.
   foreach ($Lines as $i => &$Line) {
      $Line = trim($Line);
      if (!$Line)
         continue;
      
      foreach ($SearchTerms as $Term) {
         if (($Pos = stripos($Line, $Term)) !== FALSE) {
            return MarkString($SearchTerms, SliceString($Line, $Length));
         }
      }
   }
   
   // No line was found so return the first non-blank line.
   foreach ($Lines as $Line) {
      if ($Line)
         return SliceString($Line, $Length);
   }
}

endif;