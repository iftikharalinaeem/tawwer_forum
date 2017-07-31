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
        $sQL = Gdn::sql();
        // $this->_OnlineUsers = $SQL
        // insert or update entry into table
        $session = Gdn::session();

        $frequency = c('WhosOnline.Frequency', 60);
        $history = time() - 5 * $frequency; // give bit of buffer

        // Try and grab the who's online data from the cache.
        $data = Gdn::cache()->get('WhosOnline');

        if (!$data || !array_key_exists($session->UserID, $data)) {
            $sQL
                ->select('*')
                ->from('Whosonline w')
                ->where('w.Timestamp >=', date('Y-m-d H:i:s', $history))
                ->orderBy('Timestamp', 'desc');

            if (!$session->checkPermission('Plugins.WhosOnline.ViewHidden')) {
                $sQL->where('w.Invisible', 0);
            }

            $data = $sQL->get()->resultArray();
            $data = Gdn_DataSet::index($data, 'UserID');
            Gdn::cache()->store('WhosOnline', $data, [Gdn_Cache::FEATURE_EXPIRY => $frequency]);
        }

        // Make sure the current user is shown as online.
        if ($session->UserID && !isset($data[$session->UserID])) {
            $data[$session->UserID] = [
                'UserID' => $session->UserID,
                'Timestamp' => Gdn_Format::toDateTime(),
                'Invisible' => false
            ];
        }

        Gdn::userModel()->joinUsers($data, ['UserID']);
        $onlineUsers = [];
        foreach ($data as $user) {
            $onlineUsers[$user['Name']] = $user;
        }

        ksort($onlineUsers);
        $countUsers = count($onlineUsers);
        $guestCount = WhosOnlinePlugin::guestCount();

        $this->setData('OnlineUsers', $onlineUsers);
        $this->setData('GuestCount', $guestCount);
        $this->setData('TotalCount', $countUsers + $guestCount);
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
