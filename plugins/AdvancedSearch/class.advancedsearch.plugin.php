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
   /// Properties ///
   
   public static $Types;
   
   /// Methods ///
   
   public function __construct() {
      parent::__construct();
      
      self::$Types = array(
         'discussion' => array('d' => 'discussions'),
         'comment' => array('c' => 'comments')
      );
      
      if (Gdn::PluginManager()->IsEnabled('Sphinx')) {
         if (Gdn::PluginManager()->IsEnabled('QnA')) {
            self::$Types['discussion']['question'] = 'questions';
            self::$Types['comment']['answer'] = 'answers';
         }
         
         if (Gdn::PluginManager()->IsEnabled('Polls')) {
            self::$Types['discussion']['poll'] = 'polls';
         }
      }
   }
   
   public static function MassageSearch($Search) {
      $Search = array_change_key_case($Search);
      $Search = array_map(function($v) { return is_string($v) ? trim($v) : $v; }, $Search);
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
      $CategoryID = GetValue('cat', $Search);
      if (strcasecmp($CategoryID, 'all') === 0)
         $CategoryID = null;
      
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
//      Trace($Categories, 'allowed cats');
      
      if ($CategoryID) {
         TouchValue('subcats', $Search, 0);
         if ($Search['subcats']) {
            $CategoryID = ConsolidateArrayValuesByKey(CategoryModel::GetSubtree($CategoryID), 'CategoryID');
            Trace($CategoryID, 'cats');
         }
         
         $CategoryID = array_intersect((array)$CategoryID, $Categories);
         
         if (empty($CategoryID)) {
            $Search['cat'] = FALSE;
            $Search['dosearch'] = FALSE;
         } else {
            $Search['cat'] = $CategoryID;
         }
      } else {
         $Search['cat'] = $Categories;
         unset($Search['subcategories']);
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
      
      /// Types ///
      $types = array();
      $typecount = 0;
      $selectedcount = 0;
      
      foreach (self::$Types as $table => $type) {
         $allselected = true;
         
         foreach ($type as $name => $label) {
            $typecount++;
            $key = $table.'_'.$name;
            
            if (GetValue($key, $Search)) {
               $selectedcount++;
               $types[$table][] = $name;
            } else {
               $allselected = false;
            }
            unset($Search[$key]);
         }
         // If all of the types are selected then don't filter.
         if ($allselected) {
            unset($type[$table]);
         }
      }
      
      // At least one type has to be selected to filter.
      if ($selectedcount > 0 && $selectedcount < $typecount) {
         $Search['types'] = $types;
      } else {
         unset($Search['types']);
      }
      
      
      /// Group ///
      if (!isset($Search['group']) || $Search['group']) {
         $group = true;
         
         // Check to see if we should group.
         if (isset($Search['discussionid']))
            $group = false; // searching within a discussion
         elseif (isset($Search['types']) && !isset($Search['types']['comment']))
            $group = false; // not search comments
         
         $Search['group'] = $group;
      } else {
         $Search['group'] = false;
      }
      
      if (isset($Search['discussionid']))
         unset($Search['title']);
      
      Trace($Search, 'calc search');
      return $Search;
   }
   
   public function quickSearch($title, $get = array()) {
      $Form = new Gdn_Form();
      $Form->Method = 'get';
      
      foreach ($get as $key => $value) {
         $Form->AddHidden($key, $value);
      }
      
      $result = ' <div class="QuickSearch">'.
         Anchor(' ', '#', 'QuickSearchButton Sprite SpSearch').
         '<div class="QuickSearchWrap MenuItems">';
      
      $result .= $Form->Open(array('action' => Url('/search'))).
//         $Form->Label('@'.$title, 'search').
         ' '.$Form->TextBox('search', array('placeholder' => $title)).
         ' <div class="bwrap"><button type="submit" class="Button" title="'.T('Search').'">'.T('Go').'</button></div>'.
         $Form->Close();
      
      $result .= '</div></div>';
      
      return $result;
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
   
   public function CategoriesController_PagerInit_Handler($Sender, $Args) {
      $categoryid = $Sender->Data('Category.CategoryID');
      
      if ($categoryid) {
         $name = Gdn_Format::Text($Sender->Data('Category.Name'));
         if (mb_strwidth($name) > 20) {
            $name = T('category');
         }
         
         $quickserch = $this->quickSearch(sprintf(T('Search %s'), $name), array('cat' => $categoryid));

         $Pager = $Args['Pager'];
         $Pager->HtmlAfter = $quickserch;
      }
   }
   
   public function DiscussionController_PagerInit_Handler($Sender, $Args) {
      $quickserch = $this->quickSearch(sprintf(T('Search %s'), T('discussion')), array('discussionid' => $Sender->Data('Discussion.DiscussionID')));
      
      $Pager = $Args['Pager'];
      $Pager->HtmlAfter = $quickserch;
   }
   
   public function SearchController_AutoComplete_Create($sender, $term, $limit = 5) {
      $searchModel = new SearchModel();
      $get = $sender->Request->Get();
      $get['search'] = $term;
      $results = $searchModel->autoComplete($get, $limit);
      $this->CalculateResults($results['SearchResults'], $results['SearchTerms'], !$sender->Request->Get('nomark'), 100);
      
      if (isset($get['discussionid'])) {
         // This is searching a discussion so make the user the title.
         Gdn::UserModel()->JoinUsers($results['SearchResults'], array('UserID'));
         foreach ($results['SearchResults'] as &$row) {
            $row['Title'] = htmlspecialchars($row['Name']);
         }
      }
      
      header('Content-Type: application/json; charset=utf8');
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
         
         
         // Grab the discussion if we are searching it.
         if (isset($Results['CalculatedSearch']['discussionid'])) {
            $DiscussionModel = new DiscussionModel();
            $Discussion = $DiscussionModel->GetID($Results['CalculatedSearch']['discussionid']);
            if ($Discussion) {
               $Cat = CategoryModel::Categories(GetValue('CategoryID', $Discussion));
//               if (GetValue('PermsDiscussionView', $Cat)) 
                  $Sender->SetData('Discussion', $Discussion);
            }
         }
         
      } else {
         $Results = $SearchModel->Search($Search, $Offset, $Limit);
         $Sender->SetData('SearchResults', $Results, TRUE);
         $SearchTerms = explode(' ', $SearchTerms);
      }
      Gdn::UserModel()->JoinUsers($Sender->Data['SearchResults'], array('UserID'));
      $this->CalculateResults($Sender->Data['SearchResults'], $SearchTerms, !$Sender->Request->Get('nomark'));
		
      if (isset($Sender->Data['ChildResults'])) {
         // Join the results.
         $ChildResults = $Sender->Data['ChildResults'];
         unset($Sender->Data['ChildResults']);
         $this->joinResults($Sender->Data['SearchResults'], $ChildResults, $SearchTerms);
      }
      
      $Sender->SetData('SearchTerm', implode(' ', $SearchTerms), TRUE);
      $Sender->SetData('SearchTerms', $SearchTerms, TRUE);
      $Sender->SetData('From', $Offset + 1);
      $Sender->SetData('To', $Offset + count($Sender->Data['SearchResults']));
      
      // Set the title from the search terms.
      
      
      // Make a url for related search.
      $get = array_change_key_case($Sender->Request->Get());
      unset($get['page']);
      $get['adv'] = 1;
      $url = '/search?'.http_build_query($get);
      $Sender->SetData('SearchUrl', $url);
      
      
      $this->Render('Search');
   }
   
   protected function joinResults(&$parentResults, $childResults, $searchTerms) {
      // Calculate the results.
      Gdn::UserModel()->JoinUsers($childResults, array('UserID'));
      $this->CalculateResults($childResults, $searchTerms, !Gdn::Request()->Get('nomark'));
      $childResults = Gdn_DataSet::Index($childResults, array('DiscussionID'), array('Unique' => false));
      foreach ($parentResults as &$row) {
         $row['Children'] = GetValue($row['DiscussionID'], $childResults, array());
      }
   }
   
   protected function CalculateResults(&$Data, $SearchTerms, $Mark = true, $Length = 200) {
      if (!is_array($SearchTerms)) {
         $SearchTerms = explode(' ', $SearchTerms);
         $SearchTerms = array_filter($SearchTerms, function($v) { return trim($v);  });
      }
      
      if (method_exists('SearchModel', 'addNotes'))
         $calc = array('SearchModel', 'addNotes');
      else
         $calc = function ($r) { return null; };
      
      $UseCategories = C('Vanilla.Categories.Use');
      $Breadcrumbs = array();
      
      foreach ($Data as &$Row) {
         $Row['Title'] = MarkString($SearchTerms, Gdn_Format::Text($Row['Title'], FALSE));
         $Row['Url'] = Url($Row['Url'], true);
         $Row['Score'] = (int)$Row['Score'];
//         $Row['Body'] = $Row['Summary'];
         $Row['Summary'] = SearchExcerpt(htmlspecialchars(Gdn_Format::PlainText($Row['Summary'], $Row['Format'])), $SearchTerms, $Length);
         $Row['Format'] = 'Html';
         $Row['DateHtml'] = Gdn_Format::Date($Row['DateInserted'], 'html');
         $Row['Notes'] = $calc($Row, $SearchTerms);
         
         $Type = strtolower(GetValue('Type', $Row));
         if (isset($Row['CommentID'])) {
            if ($Type == 'question')
               $Type = 'answer';
            else
               $Type = 'comment';
         } else {
            if (!$Type)
               $Type = 'discussion';
            elseif ($Type == 'page' && isset($Row['DiscussionID']))
               $Type = 'link';
         }
         $Row['Type'] = $Type;
         
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
   
function SearchExcerpt($PlainText, $SearchTerms, $Length = 200, $Mark = true) {
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
         if (($Pos = mb_stripos($Line, $Term)) !== FALSE) {
            $Line = substrWord($Line, $Term, $Length);
            
//            if ($Pos + mb_strlen($Term) > $Length) {
//               $St = -(strlen($Line) - ($Pos - $Length / 4));
//               $Pos2 = strrpos($Line, ' ', $St);
//               if ($Pos2 !== FALSE)
//                  $Line = '…'.substrWord($Line, $Pos2, $Length, "!!!");
//               else
//                  $Line = '…!'.mb_substr($Line, $St, $Length);
//            } else {
//               $Line = substrWord($Line, 0, $Length, '---');
//            }
            
            return MarkString($SearchTerms, $Line);
         }
      }
   }
   
   // No line was found so return the first non-blank line.
   foreach ($Lines as $Line) {
      if ($Line)
         return SliceString($Line, $Length);
   }
}

function substrWord($str, $start, $length, $fix = '…') {
   // If we are offsetting on a word then find it.
   if (is_string($start)) {
      $pos = mb_stripos($str, $start);
      
      $p = $pos + strlen($start);
      
      if ($pos !== false && (($pos + strlen($start)) <= $length))
         $start = 0;
      else
         $start = $pos - $length / 4;
   }
   
   // Find the word break from the offset.
   if ($start > 0) {
      $pos = mb_strpos($str, ' ', $start);
      if ($pos !== false)
         $start = $pos;
   } elseif ($start < 0) {
      $pos = mb_strrpos($str, ' ', $start);
      if ($pos !== false)
         $start = $pos;
      else
         $start = 0;
   }
   
   $len = strlen($str);
   
   if ($start + $length > $len) {
      if ($length - $start <= 0)
         $start = 0;
      else {
         // Zoom the offset back a bit.
         $pos = mb_strpos($str, ' ', max(0, $len - $length));
         if ($pos === false)
            $pos = $len - $length;
      }
   }
   
   $result = mb_substr($str, $start, $length);
   return $result;
}

endif;