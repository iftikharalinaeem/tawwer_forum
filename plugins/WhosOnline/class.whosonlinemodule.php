<?php if (!defined('APPLICATION')) exit();
/**
* Renders a list of users who are taking part in a particular discussion.
*/
class WhosOnlineModule extends Gdn_Module {

	protected $_OnlineUsers;

	public function __construct(&$Sender = '') {
		parent::__construct($Sender);
	}

	public function GetData($Invisible = FALSE) {
		$SQL = Gdn::SQL();
		// $this->_OnlineUsers = $SQL
		// insert or update entry into table
		$Session = Gdn::Session();

		$Frequency = C('WhosOnline.Frequency', 60);
		$History = time() - 5 * $Frequency; // give bit of buffer
      
      // Try and grab the who's online data from the cache.
      $Data = Gdn::Cache()->Get('WhosOnline');
      
      if (!$Data || !array_key_exists($Session->UserID, $Data)) {
         $SQL
            ->Select('*')
            ->From('Whosonline w')
            ->Where('w.Timestamp >=', date('Y-m-d H:i:s', $History))
            ->OrderBy('Timestamp', 'desc');

         if (!$Session->CheckPermission('Plugins.WhosOnline.ViewHidden'))
            $SQL->Where('w.Invisible', 0);

         $Data = $SQL->Get()->ResultArray();
         $Data = Gdn_DataSet::Index($Data, 'UserID');
         Gdn::Cache()->Store('WhosOnline', $Data, array(Gdn_Cache::FEATURE_EXPIRY => $Frequency));
      }
      
      // Make sure the current user is shown as online.
      if ($Session->UserID && !isset($Data[$Session->UserID])) {
         $Data[$Session->UserID] = array(
             'UserID' => $Session->UserID,
             'Timestamp' => Gdn_Format::ToDateTime(),
             'Invisible' => FALSE
         );
      }
      
      Gdn::UserModel()->JoinUsers($Data, array('UserID'));
      $OnlineUsers = array();
      foreach ($Data as $User)
         $OnlineUsers[$User['Name']] = $User;
      
      ksort($OnlineUsers);
      $this->_OnlineUsers = $OnlineUsers;
      $CountUsers = count($this->_OnlineUsers);
      $GuestCount = WhosOnlinePlugin::GuestCount();
      $this->_Count = $CountUsers + $GuestCount;
      $this->_GuestCount = $GuestCount;
	}

	public function AssetTarget() {
		//return 'Foot';
		return 'Panel';
	}

	public function ToString() {
      if (!$this->_OnlineUsers)
         $this->GetData();
      
      $Data = $this->_OnlineUsers;
      $Count = $this->_Count;
      
//      for ($i = 0; $i < 20; $i++) {
//         $Data[] = $Data[0];
//      }
      
		$String = '';
		$Session = Gdn::Session();
		ob_start();
      $DisplayStyle = C('WhosOnline.DisplayStyle', 'list');
		?>
      <div id="WhosOnline" class="Box">
         <h4><?php echo T("Who's Online"); ?> <span class="Count"><?php echo Gdn_Format::BigNumber($Count, 'html') ?></span></h4>
         <?php
         if ($Count > 0) {
            if ($DisplayStyle == 'pictures') {
               if (count($Data) > 10) {
                  $ListClass= 'PhotoGrid PhotoGridSmall';
               } else {
                  $ListClass= 'PhotoGrid';
               }

               echo '<div class="'.$ListClass.'">';

               foreach ($Data as $User) {
                  if (!$User['Photo'] && !function_exists('UserPhotoDefaultUrl')) {
                     $User['Photo'] = Asset('/applications/dashboard/design/images/usericon.gif', TRUE);
                  }
                  
                  echo UserPhoto($User, array(
                     'LinkClass' => (($User['Invisible']) ? 'Invisible' : '')
                  ));
               }
               
               if ($this->_GuestCount) {
                  $GuestCount = Gdn_Format::BigNumber($this->_GuestCount, 'html');
                  $GuestsText = Plural($this->_GuestCount, 'guest', 'guests');
                  $Plus = $Count == $GuestCount ? '' : '+';
                  echo <<<EOT
 <span class="GuestCountBox"><span class="GuestCount">{$Plus}$GuestCount</span> <span class="GuestLabel">$GuestsText</span></span>
EOT;
               }

               echo '</div>';
            } else {
               echo '<ul class="PanelInfo">';

               foreach ($Data as $User) {
                  echo '<li>'.UserAnchor($User, ($User['Invisible']) ? 'Invisible' : '').'</li>';
               }
               
               if ($this->_GuestCount) {
                  echo '<li><strong>'.sprintf(T('+%s Guests'), Gdn_Format::BigNumber($this->_GuestCount, 'html')).'</strong></li>';
               }

               echo '</ul>';
            }
         }
         ?>
		</div>
		<?php
		$String = ob_get_contents();
		@ob_end_clean();
		return $String;
	}
}
