<?php

/**
 * Renders a list of users who are taking part in a particular discussion.
 */
class WhosOnlineModule extends Gdn_Module {

    /**
     * WhosOnlineModule constructor.
     *
     * @param string $sender
     */
    public function __construct($sender = '') {
        parent::__construct($sender);
        $this->_ApplicationFolder = 'plugins/WhosOnline';
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
        $CountUsers = count($OnlineUsers);
        $GuestCount = WhosOnlinePlugin::guestCount();

        $this->setData('OnlineUsers', $OnlineUsers);
        $this->setData('GuestCount', $GuestCount);
        $this->setData('TotalCount', $CountUsers + $GuestCount);
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
        if (!$this->data('OnlineUsers')) {
            $this->getData();
        }

        return parent::toString();
    }
}
