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

		$Invisible = ($Invisible ? 1 : 0);

		if ($Session->UserID) {
         $Timestamp = Gdn_Format::ToDateTime();
         
         $Px = $SQL->Database->DatabasePrefix;
         $Sql = "insert {$Px}Whosonline (UserID, Timestamp, Invisible) values ({$Session->UserID}, :Timestamp, :Invisible) on duplicate key update Timestamp = :Timestamp1, Invisible = :Invisible1";
         $SQL->Database->Query($Sql, array(':Timestamp' => $Timestamp, ':Invisible' => $Invisible, ':Timestamp1' => $Timestamp, ':Invisible1' => $Invisible));
         
//			$SQL->Replace('Whosonline', array(
//				'UserID' => $Session->UserID,
//				'Timestamp' => Gdn_Format::ToDateTime(),
//				'Invisible' => $Invisible),
//				array('UserID' => $Session->UserID)
//			);
      }

		$Frequency = C('WhosOnline.Frequency', 60);
		$History = time() - 2 * $Frequency; // give bit of buffer
      
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
      
      Gdn::UserModel()->JoinUsers($Data, array('UserID'));
      $OnlineUsers = array();
      foreach ($Data as $User)
         $OnlineUsers[$User['Name']] = $User;
      
      ksort($OnlineUsers);
      $this->_OnlineUsers = $OnlineUsers;
	}

	public function AssetTarget() {
		//return 'Foot';
		return 'Panel';
	}

	public function ToString() {
      if (!$this->_OnlineUsers)
         $this->GetData();
      
      $Data = $this->_OnlineUsers;
      
//      for ($i = 0; $i < 20; $i++) {
//         $Data[] = $Data[0];
//      }
      
		$String = '';
		$Session = Gdn::Session();
		ob_start();
      $DisplayStyle = C('WhosOnline.DisplayStyle', 'list');
		?>
      <div id="WhosOnline" class="Box">
         <h4><?php echo T("Who's Online"); ?> <span class="Count"><?php echo count($Data) ?></span></h4>
         <?php
         if (count($Data) > 0) {
            if ($DisplayStyle == 'pictures') {
               if (count($Data) > 10) {
                  $ListClass= 'PhotoGridSmall';
               } else {
                  $ListClass= 'PhotoGrid';
               }

               echo '<div class="'.$ListClass.'">';

               foreach ($Data as $User) {
                  if (!$User['Photo'] && !function_exists('UserPhotoDefaultUrl')) {
                     $User['Photo'] = Asset('/applications/dashboard/design/images/usericon.gif', TRUE);
                  }
                  
                  echo UserPhoto($User);
               }

               echo '</div>';
            } else {
               echo '<ul class="PanelInfo">';

               foreach ($Data as $User) {
                  echo '<li><strong>'.UserAnchor($User).'</strong><br /></li>';
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
