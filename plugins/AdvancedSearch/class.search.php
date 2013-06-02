<?php


/**
 * A utility class to help with searches.
 */
class Search {
   /// Properties ///
   protected static $types;
   
   
   /// Methods ///
   
   /**
    * Massage an advanced style search query for correctness.
    * 
    * @param array $search
    * @return array
    */
   public static function cleanSearch($search) {
     $search = array_change_key_case($search);
     $search = array_map(function($v) { return is_string($v) ? trim($v) : $v; }, $search);
     $search = array_filter($search, function($v) { return $v !== ''; });
     TouchValue('dosearch', $search, true);
     
     /// Author ///
     if (isset($search['author'])) {
        $Usernames = explode(',', $search['author']);
        $Usernames = array_map('trim', $Usernames);
        $Usernames = array_filter($Usernames);

        $Users = Gdn::SQL()->Select('UserID, Name')->From('User')->Where('Name', $Usernames)->Get()->ResultArray();
        if (count($Usernames) == 1 && empty($Users)) {
           // Searching for one author that doesn't exist.
           $search['dosearch'] = FALSE;
        }

        if (!empty($Users))
           $search['users'] = $Users;
     }

     /// Category ///
     $CategoryFilter = array();
     $Archived = GetValue('archived', $search, 0);
     $CategoryID = GetValue('cat', $search);
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
     $Categories[0] = true; // allow uncategorized too.
     $Categories = array_keys($Categories);
   //      Trace($Categories, 'allowed cats');

     if ($CategoryID) {
        TouchValue('subcats', $search, 0);
        if ($search['subcats']) {
           $CategoryID = ConsolidateArrayValuesByKey(CategoryModel::GetSubtree($CategoryID), 'CategoryID');
           Trace($CategoryID, 'cats');
        }

        $CategoryID = array_intersect((array)$CategoryID, $Categories);

        if (empty($CategoryID)) {
           $search['cat'] = FALSE;
           $search['dosearch'] = FALSE;
        } else {
           $search['cat'] = $CategoryID;
        }
     } else {
        $search['cat'] = $Categories;
        unset($search['subcategories']);
     }

     /// Date ///
     if (isset($search['date'])) {
        // Try setting the date.
        $Timestamp = strtotime($search['date']);
        if ($Timestamp) {
           $search['houroffset'] = Gdn::Session()->HourOffset();
           $Timestamp += -Gdn::Session()->HourOffset() * 3600;
           $search['date'] = Gdn_Format::ToDateTime($Timestamp);

           if (isset($search['within'])) {
              $search['timestamp-from'] = strtotime('-'.$search['within'], $Timestamp);
              $search['timestamp-to'] = strtotime('+'.$search['within'], $Timestamp);
           } else {
              $search['timestamp-from'] = $search['date'];
              $search['timestamp-to'] = strtotime('+1 day', $Timestamp);
           }
           $search['date-from'] = Gdn_Format::ToDateTime($search['timestamp-from']);
           $search['date-to'] = Gdn_Format::ToDateTime($search['timestamp-to']);
        } else {
           unset($search['date']);
        }
     } else {
        unset($search['within']);
     }

     /// Tags ///
     if (isset($search['tags'])) {
        $Tags = explode(',', $search['tags']);
        $Tags = array_map('trim', $Tags);
        $Tags = array_filter($Tags);

        $TagData = Gdn::SQL()->Select('TagID, Name')->From('Tag')->Where('Name', $Tags)->Get()->ResultArray();
        if (count($Tags) == 1 && empty($TagData)) {
           // Searching for one author that doesn't exist.
           $search['dosearch'] = FALSE;
           unset($search['tags']);
        }

        if (!empty($TagData)) {
           $search['tags'] = $TagData;
           TouchValue('tags-op', $search, 'or');
        } else {
           unset($search['tags'], $search['tags-op']);
        }

     }

     /// Types ///
     $types = array();
     $typecount = 0;
     $selectedcount = 0;

     foreach (self::types() as $table => $type) {
        $allselected = true;

        foreach ($type as $name => $label) {
           $typecount++;
           $key = $table.'_'.$name;

           if (GetValue($key, $search)) {
              $selectedcount++;
              $types[$table][] = $name;
           } else {
              $allselected = false;
           }
           unset($search[$key]);
        }
        // If all of the types are selected then don't filter.
        if ($allselected) {
           unset($type[$table]);
        }
     }

     // At least one type has to be selected to filter.
     if ($selectedcount > 0 && $selectedcount < $typecount) {
        $search['types'] = $types;
     } else {
        unset($search['types']);
     }


     /// Group ///
     if (!isset($search['group']) || $search['group']) {
        $group = true;

        // Check to see if we should group.
        if (isset($search['discussionid']))
           $group = false; // searching within a discussion
        elseif (isset($search['types']) && !isset($search['types']['comment']))
           $group = false; // not search comments

        $search['group'] = $group;
     } else {
        $search['group'] = false;
     }

     if (isset($search['discussionid']))
        unset($search['title']);

     Trace($search, 'calc search');
     return $search;
   }
   
   public static function youtube($id) {
      return <<<EOT
<span class="Video YouTube" id="youtube-$id"><span class="VideoPreview"><a href="http://youtube.com/watch?v=$id"><img src="http://img.youtube.com/vi/$id/0.jpg" /></a></span><span class="VideoPlayer"></span></span>
EOT;
   }
   
   public static function vimeo($id) {
      // width="500" height="281"
      return <<<EOT
<iframe src="http://player.vimeo.com/video/$id?badge=0" frameborder="0" class="Video Vimeo" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>
EOT;
   }
   
   public static function extractMedia($html) {
      $result = array();
      if (preg_match_all('`src="([^"]+)"`', $html, $matches)) {
         foreach ($matches[1] as $src) {
            $row = array(
               'type' => 'img',
               'src' => $src,
               'href' => $src,
               'preview' => Img($src)
               );
            
            $parts = parse_url($src);
            
            if (isset($parts['host'])) {
               switch($parts['host']) {
                  case 'img.youtube.com':
                     if (preg_match('`/vi/([^/]+)/\d+.jpg`i', $src, $m)) {
                        $row['type'] = 'video';
                        $row['subtype'] = 'youtube';
                        $id = urlencode($m[1]);
                        $row['href'] = 'https://www.youtube.com/watch?v='.$id;
                        $row['preview'] = self::youtube($id);
                     }
                     break;
                  case 'www.youtube.com':
                     if (preg_match('`/embed/([a-z0-9])`i', $src, $m)) {
                        $row['type'] = 'video';
                        $row['subtype'] = 'youtube';
                        $id = urlencode($m[1]);
                        $row['href'] = 'https://www.youtube.com/watch?v='.$id;
                        $row['preview'] = self::youtube($id);
                     }
                     break;
                  case 'vimeo.com':
                     $id = false;
                     // Try the querystring.
                     trace($parts);
                     if (isset($parts['query'])) {
                        parse_str($parts['query'], $get);
                        trace($get);
                        if (isset($get['clip_id']))
                           $id = $get['clip_id'];
                     }
                     
                     if ($id) {
                        $row['type'] = 'video';
                        $row['subtype'] = 'vimeo';
                        $row['href'] = 'http://vimeo.com/'.$id;
                        $row['preview'] = self::vimeo($id);
                     }
                     break;
               }
            }
            
            
            $result[] = $row;
         }
      }
      return $result;
   }
   
   /**
    * Return an array of all of the valid search types.
    */
   public static function types() {
      if (!isset(self::$types)) {
         $types = array(
            'discussion' => array('d' => 'discussions'),
            'comment' => array('c' => 'comments')
         );
         
         if (Gdn::PluginManager()->IsEnabled('QnA')) {
            $types['discussion']['question'] = 'questions';
            $types['comment']['answer'] = 'answers';
         }

         if (Gdn::PluginManager()->IsEnabled('Polls')) {
            $types['discussion']['poll'] = 'polls';
         }
         
         if (Gdn::ApplicationManager()->CheckApplication('Pages')) {
            $types['page']['p'] = 'docs';
         }
         
         self::$types = $types;
      }
      
      return self::$types;
   }
}
