<?php

/**
 * Renders a list of users who are taking part in a particular discussion.
 */
class WhosOnlineModule extends Gdn_Module {

    protected $_OnlineUsers;

    /**
     * WhosOnlineModule constructor.
     *
     * @param string $sender
     */
    public function __construct($sender = '') {
        parent::__construct($sender);
    }

    /**
     *
     */
    public function getData() {
        $SQL = Gdn::sql();
        // $this->_OnlineUsers = $SQL
        // insert or update entry into table
        $Session = Gdn::session();

        $Frequency = c('WhosOnline.Frequency', 60);
        $History = time() - 5 * $Frequency; // give bit of buffer

        // Try and grab the who's online data from the cache.
        $Data = Gdn::cache()->get('WhosOnline');

        if (!$Data || !array_key_exists($Session->UserID, $Data)) {
            $SQL
                ->select('*')
                ->from('Whosonline w')
                ->where('w.Timestamp >=', date('Y-m-d H:i:s', $History))
                ->orderBy('Timestamp', 'desc');

            if (!$Session->checkPermission('Plugins.WhosOnline.ViewHidden')) {
                $SQL->where('w.Invisible', 0);
            }

            $Data = $SQL->get()->resultArray();
            $Data = Gdn_DataSet::index($Data, 'UserID');
            Gdn::cache()->store('WhosOnline', $Data, [Gdn_Cache::FEATURE_EXPIRY => $Frequency]);
        }

        // Make sure the current user is shown as online.
        if ($Session->UserID && !isset($Data[$Session->UserID])) {
            $Data[$Session->UserID] = [
                'UserID' => $Session->UserID,
                'Timestamp' => Gdn_Format::toDateTime(),
                'Invisible' => false
            ];
        }

        Gdn::userModel()->joinUsers($Data, ['UserID']);
        $OnlineUsers = [];
        foreach ($Data as $User) {
            $OnlineUsers[$User['Name']] = $User;
        }

        ksort($OnlineUsers);
        $this->_OnlineUsers = $OnlineUsers;
        $CountUsers = count($this->_OnlineUsers);
        $GuestCount = WhosOnlinePlugin::guestCount();
        $this->_Count = $CountUsers + $GuestCount;
        $this->_GuestCount = $GuestCount;
    }

    /**
     * @return string
     */
    public function assetTarget() {
        return 'Panel';
    }

    /**
     * @return string
     */
    public function toString() {
        if (!$this->_OnlineUsers) {
            $this->getData();
        }

        $Data = $this->_OnlineUsers;
        $Count = $this->_Count;

        ob_start();
        $DisplayStyle = c('WhosOnline.DisplayStyle', 'list');
        ?>
        <div id="WhosOnline" class="Box">
            <h4><?php echo t("Who's Online"); ?>
                <span class="Count"><?php echo Gdn_Format::bigNumber($Count, 'html') ?></span>
            </h4>
            <?php
            if ($Count > 0) {
                if ($DisplayStyle == 'pictures') {
                    if (count($Data) > 10) {
                        $ListClass = 'PhotoGrid PhotoGridSmall';
                    } else {
                        $ListClass = 'PhotoGrid';
                    }

                    echo '<div class="'.$ListClass.'">';

                    foreach ($Data as $User) {
                        if (!$User['Photo'] && !function_exists('UserPhotoDefaultUrl')) {
                            $User['Photo'] = asset('/applications/dashboard/design/images/usericon.gif', true);
                        }

                        echo userPhoto($User, [
                            'LinkClass' => (($User['Invisible']) ? 'Invisible' : '')
                        ]);
                    }

                    if ($this->_GuestCount) {
                        $GuestCount = Gdn_Format::bigNumber($this->_GuestCount, 'html');
                        $GuestsText = plural($this->_GuestCount, 'guest', 'guests');
                        $Plus = $Count == $GuestCount ? '' : '+';
                        echo <<<EOT
        <span class="GuestCountBox"><span class="GuestCount">{$Plus}$GuestCount</span> <span class="GuestLabel">$GuestsText</span></span>
EOT;
                    }

                    echo '</div>';
                } else {
                    echo '<ul class="PanelInfo">';

                    foreach ($Data as $User) {
                        echo '<li>'.userAnchor($User, ($User['Invisible']) ? 'Invisible' : '').'</li>';
                    }

                    if ($this->_GuestCount) {
                        echo '<li><strong>'.sprintf(t('+%s Guests'), Gdn_Format::bigNumber($this->_GuestCount, 'html')).'</strong></li>';
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
