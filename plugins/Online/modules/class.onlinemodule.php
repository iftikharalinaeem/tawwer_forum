<?php if (!defined('APPLICATION')) exit();

/**
 * Online Plugin - OnlineModule
 *
 * This module displays a list of users who are currently online, either in
 * user icon format, or as a simple user list.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license Proprietary
 * @package Misc
 */

class OnlineModule extends Gdn_Module {

   /**
    * List of online users for this context
    * @var array
    */
   protected $onlineUsers;

   /**
    * Whether to draw Invisible users (admin permission)
    * @var boolean
    */
   protected $showInvisible;

   /**
    * Render style. 'pictures' or 'links'
    * @var string
    */
   public $style;

   protected $count;
   protected $onlineCount;
   protected $guestCount;

   public $selector = NULL;
   public $selectorID = NULL;
   public $selectorField = NULL;

   public $contextID = FALSE;
   public $contextField = FALSE;

   public $showGuests = TRUE;

   public function __construct(&$sender = '') {
      parent::__construct($sender);
      $this->onlineUsers = NULL;
      $this->showInvisible = Gdn::session()->checkPermission('Plugins.Online.ViewHidden');
      $this->style = C('Plugins.Online.Style', OnlinePlugin::DEFAULT_STYLE);
      $this->selector = 'auto';
   }

   public function __set($name, $value) {
      switch ($name) {
         case 'CategoryID':
            $this->selector = 'category';
            $this->selectorID = $value;
            $this->selectorField = 'CategoryID';
            break;

         case 'DiscussionID':
            $this->selector = 'discussion';
            $this->selectorID = $value;
            $this->selectorField = 'DiscussionID';
            break;
      }
   }

   /**
    * Get the list of currently online users for this context
    *
    * Also calculate counts.
    */
   public function getData() {
      if (is_null($this->onlineUsers)) {

         // Find out where we are
         $this->lockOn();

         $this->onlineUsers = OnlinePlugin::instance()->onlineUsers($this->selector, $this->selectorID, $this->selectorField);

         if (!array_key_exists(Gdn::session()->User->UserID, $this->onlineUsers)) {
            $this->onlineUsers[Gdn::session()->UserID] = array(
               'UserID'                   => Gdn::session()->UserID,
               'Timestamp'                => date('Y-m-d H:i:s'),
               'Location'                 => $this->selector,
               "{$this->selectorField}"   => $this->selectorID,
               'Visible'                  => !OnlinePlugin::instance()->privateMode(Gdn::session()->User)
            );
         }
         Gdn::userModel()->joinUsers($this->onlineUsers, array('UserID'));

         // Strip invisibles, and index by username
         $onlineUsers = array();
         foreach ($this->onlineUsers as $user) {
            if (!$user['Visible'] && !$this->showInvisible) continue;
            $onlineUsers[$user['Name']] = $user;
         }

         ksort($onlineUsers);
         $this->onlineUsers = $onlineUsers;
      }

      $onlineCount = count($this->onlineUsers);
      $guestCount = OnlinePlugin::guests();
      $this->count = $onlineCount + $guestCount;
      $this->onlineCount = $onlineCount;
      $this->guestCount = $guestCount;
   }

   public function assetTarget() {
      return 'Panel';
   }

   /**
    * Determine current viewing location
    *
    * Fill in Selector and Context.
    */
   public function lockOn() {
      if ($this->selector == 'auto') {

         $location = OnlinePlugin::whereAmI(
            Gdn::controller()->ResolvedPath,
            Gdn::controller()->ReflectArgs
         );

         switch ($location) {
            case 'category':
            case 'discussion':
            case 'comment':
               $this->showGuests = FALSE;
               $this->selector = 'category';
               $this->selectorField = 'CategoryID';

               if ($location == 'category') {
                  $this->selectorID = Gdn::controller()->data('Category.CategoryID');
                  $this->contextField = FALSE;
                  $this->contextID = FALSE;
               } else {
                  $this->selectorID = Gdn::controller()->data('Discussion.CategoryID');
                  $this->contextField = 'DiscussionID';
                  $this->contextID = Gdn::controller()->data('Discussion.DiscussionID');
               }

               break;

            case 'limbo':
            case 'all':
               $this->showGuests = TRUE;
               $this->selector = 'all';
               $this->selectorID = NULL;
               $this->selectorField = NULL;
               break;
         }
      }
   }

   public function ToString() {
      $this->lockOn();

      // Check cache
      switch ($this->selector) {
         case 'category':
         case 'discussion':
            $selectorID = is_null($this->selectorID) ? 'all' : $this->selectorID;
            $selectorStub = "{$selectorID}-{$this->selectorField}";
            break;

         case 'limbo':
            $selectorStub = 'all';
            break;

         case 'all':
         default:
            $selectorStub = 'all';
            break;
      }

      // Check cache for matching pre-built data
      $renderedCacheKey = sprintf(OnlinePlugin::CACHE_ONLINE_MODULE_KEY, $this->selector, $selectorStub);
      $preRender = Gdn::cache()->get($renderedCacheKey);
      if ($preRender !== Gdn_Cache::CACHEOP_FAILURE)
         return $preRender;

      $this->getData();

      uksort($this->onlineUsers, 'strnatcasecmp');

      $outputString = '';
      ob_start();

      $trackCount = ($this->showGuests) ? $this->count : $this->onlineCount;
      switch ($this->selector) {
         case 'category':
            $title = T("Who's Online in this Category");
            break;
         case 'discussion':
         case 'comment':
            $title = T("Who's Online in this Discussion");
         case 'limbo':
         case 'all':
         default:
            $title = T("Who's Online");
      }

      ?>
      <div id="WhosOnline" class="WhosOnline Box">
         <h4><?php echo $title; ?> <span class="Count"><?php echo Gdn_Format::bigNumber($trackCount, 'html') ?></span></h4>
         <?php

         if ($this->count > 0) {
            if ($this->style == 'pictures') {
               $listClass = 'PhotoGrid';
               if (count($this->onlineUsers) > 10)
                  $listClass .= ' PhotoGridSmall';

               echo '<div class="'.$listClass.'">'."\n";
               if ($this->onlineCount) {
                  foreach ($this->onlineUsers as $user) {
                     $wrapClass = array('OnlineUserWrap', 'UserPicture');
                     $wrapClass[] = ((!$user['Visible']) ? 'Invisible' : '');

                     if (!$user['Photo'] && !function_exists('UserPhotoDefaultUrl'))
                        $user['Photo'] = asset('/applications/dashboard/design/images/usericon.gif', TRUE);

                     if ($this->selector == 'category' && $this->contextField)
                        if (val($this->contextField, $user, NULL) == $this->contextID)
                           $wrapClass[] = 'InContext';

                     $wrapClass = implode(' ', $wrapClass);
                     echo "<div class=\"{$wrapClass}\">";
                     echo userPhoto($user);

                     $userName = val('Name', $user, FALSE);
                     if ($userName)
                        echo wrap($userName, 'div', array('class' => 'OnlineUserName'));
                     echo '</div>';
                  }
               }

               if ($this->guestCount && $this->showGuests) {
                  $guestCount = Gdn_Format::bigNumber($this->guestCount, 'html');
                  $guestsText = plural($this->guestCount, 'Guest', 'Guests');
                  $plus = $this->count == $this->guestCount ? '' : '+';
                  echo <<<EOT
<span class="GuestCountBox"><span class="GuestCount">{$plus}$guestCount</span> <span class="GuestLabel">$guestsText</span></span>
EOT;
               }
               echo '</div>'."\n";

            } else {

               echo '<ul class="PanelInfo">'."\n";
               if ($this->onlineCount) {
                  foreach ($this->onlineUsers as $user) {
                     $wrapClass = array('OnlineUserWrap', 'UserLink');
                     $wrapClass[] = ((!$user['Visible']) ? 'Invisible' : '');
                     if ($this->selector == 'category' && $this->contextField)
                        if (val($this->contextField, $user, NULL) == $this->contextID)
                           $wrapClass .= ' InContext';

                     $wrapClass = implode(' ', $wrapClass);
                     echo "<li class=\"{$wrapClass}\">".userAnchor($user)."</li>\n";
                  }
               }

               if ($this->guestCount && $this->showGuests) {
                  $guestCount = Gdn_Format::bigNumber($this->guestCount, 'html');
                  $guestsText = plural($this->guestCount, 'Guest', 'Guests');
                  $plus = $this->count == $this->guestCount ? '' : '+';
                  echo <<<EOT
<li class="GuestCountText"><span class="GuestCount"><strong>{$plus}{$guestCount}</strong></span> <span class="GuestLabel"><strong>{$guestsText}</strong></span></li>\n
EOT;
               }
               echo '</ul>'."\n";
            }
         }
         ?>
      </div>
      <?php

      $outputString = ob_get_contents();
      @ob_end_clean();

      // Store rendered data
      Gdn::cache()->store($renderedCacheKey, $outputString, array(
          Gdn_Cache::FEATURE_EXPIRY => OnlinePlugin::instance()->cacheRenderDelay
      ));

      return $outputString;
   }
}
