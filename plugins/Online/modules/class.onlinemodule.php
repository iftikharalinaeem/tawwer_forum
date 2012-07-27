<?php if (!defined('APPLICATION')) exit();

/**
 * Online Plugin - OnlineModule
 * 
 * This module displays a list of users who are currently online, either in
 * user icon format, or as a simple user list.
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Misc
 */

class OnlineModule extends Gdn_Module {

   /**
    * List of online users
    * @var array
    */
	protected $OnlineUsers;
   
   /**
    * Whether to draw Invisible users (admin permission)
    * @var boolean
    */
   protected $ShowInvisible;
   
   /**
    * Render style. 'pictures' or 'links'
    * @var string
    */
   public $Style;
   
   protected $Count;
   protected $OnlineCount;
   protected $GuestCount;
   
   public $Selector = NULL;
   public $SelectorID = NULL;
   public $SelectorField = NULL;
   
   public $ContextID = FALSE;
   public $ContextField = FALSE;
   
   public $ShowGuests = TRUE;

	public function __construct(&$Sender = '') {
		parent::__construct($Sender);
      $this->OnlineUsers = NULL;
      $this->ShowInvisible = Gdn::Session()->CheckPermission('Plugins.Online.ViewHidden');
      $this->Style = C('Plugins.Online.Style', OnlinePlugin::DEFAULT_STYLE);
      $this->Selector = 'auto';
	}
   
   public function __set($Name, $Value) {
      switch ($Name) {
         case 'CategoryID':
            $this->Selector = 'category';
            $this->SelectorID = $Value;
            $this->SelectorField = 'CategoryID';
            break;
         
         case 'DiscussionID':
            $this->Selector = 'discussion';
            $this->SelectorID = $Value;
            $this->SelectorField = 'DiscussionID';
            break;
      }
   }

	public function GetData() {
		if (is_null($this->OnlineUsers)) {
         if ($this->Selector == 'auto') {
            
            $Location = OnlinePlugin::WhereAmI(
               Gdn::Controller()->ResolvedPath, 
               Gdn::Controller()->ReflectArgs
            );
            
            switch ($Location) {
               case 'category':
               case 'discussion':
               case 'comment':
                  $this->ShowGuests = FALSE;
                  $this->Selector = 'category';
                  $this->SelectorField = 'CategoryID';
                  
                  if ($Location == 'category') {
                     $this->SelectorID = Gdn::Controller()->Data('Category.CategoryID');
                     $this->ContextField = FALSE;
                     $this->ContextID = FALSE;
                  } else {
                     $this->SelectorID = Gdn::Controller()->Data('Discussion.CategoryID');
                     $this->ContextField = 'DiscussionID';
                     $this->ContextID = Gdn::Controller()->Data('Discussion.DiscussionID');
                  }
                  
                  break;
               
               case 'limbo':
               case 'all':
                  $this->ShowGuests = TRUE;
                  $this->Selector = 'all';
                  $this->SelectorID = NULL;
                  $this->SelectorField = NULL;
                  break;
            }
         }
         
         $this->OnlineUsers = OnlinePlugin::Instance()->OnlineUsers($this->Selector, $this->SelectorID, $this->SelectorField);
         
         if (!array_key_exists(Gdn::Session()->User->UserID, $this->OnlineUsers)) {
            $this->OnlineUsers[Gdn::Session()->UserID] = array(
               'UserID'                   => Gdn::Session()->UserID,
               'Timestamp'                => date('Y-m-d H:i:s'),
               'Location'                 => $this->Selector,
               "{$this->SelectorField}"   => $this->SelectorID,
               'Visible'                  => !OnlinePlugin::Instance()->PrivateMode(Gdn::Session()->User)
            );
         }
         Gdn::UserModel()->JoinUsers($this->OnlineUsers, array('UserID'));
         
         // Strip invisibles, and index by username
         $OnlineUsers = array();
         foreach ($this->OnlineUsers as $User) {
            if (!$User['Visible'] && !$this->ShowInvisible) continue;
            $OnlineUsers[$User['Name']] = $User;
         }

         ksort($OnlineUsers);
         $this->OnlineUsers = $OnlineUsers;
      }
      
      $CountUsers = count($this->OnlineUsers);
      $GuestCount = OnlinePlugin::Guests();
      $this->Count = $CountUsers + $GuestCount;
      $this->OnlineCount = $CountUsers;
      $this->GuestCount = $GuestCount;
	}

	public function AssetTarget() {
		return 'Panel';
	}

	public function ToString() {
      $this->GetData();
      
		$OutputString = '';
		ob_start();
      
      $TrackCount = ($this->ShowGuests) ? $this->Count : $this->OnlineCount;
      switch ($this->Selector) {
         case 'category':
            $Title = T("Who's Online in this Category");
            break;
         case 'discussion':
         case 'comment':
            $Title = T("Who's Online in this Discussion");
         case 'limbo':
         case 'all':
         default:
            $Title = T("Who's Online");
      }
      
		?>
      <div id="WhosOnline" class="WhosOnline Box">
         <h4><?php echo $Title; ?> <span class="Count"><?php echo Gdn_Format::BigNumber($TrackCount, 'html') ?></span></h4>
         <?php
         
//         $SetUser = NULL;
//         foreach ($this->OnlineUsers as $User) {
//            $SetUser = $User;
//            break;
//         }
//         
//         $SetUser['Name'] = 'Lewie Stepdad';
//         $this->OnlineUsers = array_fill(0, 200, $SetUser);
//         $this->OnlineCount = sizeof($this->OnlineUsers);
         
         if ($this->Count > 0) {
            if ($this->Style == 'pictures') {
               $ListClass = 'PhotoGrid';
               if (count($this->OnlineUsers) > 10)
                  $ListClass .= ' PhotoGridSmall';
               
               echo '<div class="'.$ListClass.'">'."\n";
               foreach ($this->OnlineUsers as $User) {
                  $LinkClass = ((!$User['Visible']) ? 'Invisible' : '');
                  
                  if (!$User['Photo'] && !function_exists('UserPhotoDefaultUrl'))
                     $User['Photo'] = Asset('/applications/dashboard/design/images/usericon.gif', TRUE);
                  
                  if ($this->Selector == 'category' && $this->ContextField)
                     if (GetValue($this->ContextField, $User, NULL) == $this->ContextID)
                        $LinkClass .= ' InContext';
                  
                  $FullUser = Gdn::UserModel()->GetID(GetValue('UserID', $User), DATASET_TYPE_ARRAY);
                  $WrapClass = "OnlineUserWrap";
                  $UserCssClass = GetValue('_CssClass', $FullUser);
                  if ($UserCssClass)
                     $WrapClass .= " {$UserCssClass}";
                     
                  echo "<div class=\"{$WrapClass}\">";
                  echo UserPhoto($User, array(
                     'LinkClass' => $LinkClass
                  ));
                  echo Wrap(UserAnchor($User), 'div', array('class' => 'OnlineUserName'));
                  echo '</div>';
               }
               
               if ($this->GuestCount && $this->ShowGuests) {
                  $GuestCount = Gdn_Format::BigNumber($this->GuestCount, 'html');
                  $GuestsText = Plural($this->GuestCount, 'Guest', 'Guests');
                  $Plus = $this->Count == $this->GuestCount ? '' : '+';
                  echo <<<EOT
<span class="GuestCountBox"><span class="GuestCount">{$Plus}$GuestCount</span> <span class="GuestLabel">$GuestsText</span></span>
EOT;
               }
               echo '</div>'."\n";
               
            } else {
               
               echo '<ul class="PanelInfo">'."\n";
               foreach ($this->OnlineUsers as $User) {
                  $LinkClass = ((!$User['Visible']) ? 'Invisible' : '');
                  if ($this->Selector == 'category' && $this->ContextField)
                     if (GetValue($this->ContextField, $User, NULL) == $this->ContextID)
                        $LinkClass .= ' InContext';
                  
                  $FullUser = Gdn::UserModel()->GetID(GetValue('UserID', $User), DATASET_TYPE_ARRAY);
                  $WrapClass = "OnlineUserWrap";
                  $UserCssClass = GetValue('_CssClass', $FullUser);
                  if ($UserCssClass)
                     $WrapClass .= " {$UserCssClass}";
                     
                  echo "<li class=\"{$WrapClass}\">".UserAnchor($User, $LinkClass)."</li>\n";
               }
               
               if ($this->GuestCount && $this->ShowGuests) {
                  $GuestCount = Gdn_Format::BigNumber($this->GuestCount, 'html');
                  $GuestsText = Plural($this->GuestCount, 'Guest', 'Guests');
                  $Plus = $this->Count == $this->GuestCount ? '' : '+';
                  echo <<<EOT
<li class="GuestCountText"><span class="GuestCount"><strong>{$Plus}{$GuestCount}</strong></span> <span class="GuestLabel"><strong>{$GuestsText}</strong></span></li>\n
EOT;
               }
               echo '</ul>'."\n";
            }
         }
         ?>
		</div>
		<?php
      
		$OutputString = ob_get_contents();
		@ob_end_clean();
      
		return $OutputString;
	}
}
