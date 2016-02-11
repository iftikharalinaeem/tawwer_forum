<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

$PluginInfo['KeywordBlocker'] = array(
    'Name' => 'Keyword Blocker',
    'Description' => 'Block posts containing certain words and send them for review.',
    'Version' => '1.0',
    'RequiredApplications' => array('Vanilla' => '2.2'),
    'HasLocale' => false,
    'License' => 'GNU GPL2',
    'SettingsUrl' => '/settings/keywordBlocker',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'Author' => 'Alexandre (DaazKu) Chouinard',
    'AuthorEmail' => 'alexandre.c@vanillaforums.com'
);

/**
 * Class KeywordBlocker
 */
class KeywordBlockerPlugin extends Gdn_Plugin {

    /**
     * Create a method called "keywordBlocker" on the SettingController.
     *
     * @param $sender Sending controller instance
     */
    public function settingsController_keywordBLocker_create($sender) {

        $sender->title(sprintf(t('%s settings'), t('Keyword Blocker')));
        $sender->addSideMenu('settings/keywordBlocker');

        $sender->Form = new Gdn_Form();

        $this->dispatch($sender, $sender->RequestArgs);
    }

    /**
     * Define what to do for the /index page of this plugin.
     *
     * @param $sender Sending controller instance
     */
    public function controller_index($sender) {
        // Prevent non-admins from accessing this page
        $sender->permission('Garden.Settings.Manage');
        $sender->setData('PluginDescription', $this->getPluginKey('Description'));

        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField(array(
            'KeywordBlocker.BlockMode' => c('KeywordBlocker.BlockMode', 'Moderation'),
            'KeywordBlocker.Words',
        ));

        $sender->Form->setModel($configurationModel);

        // If seeing the form for the first time...
        if ($sender->Form->authenticatedPostBack() === false) {
            $sender->Form->setData($configurationModel->Data);
        } else {
            if ($sender->Form->save()) {
                $sender->StatusMessage = t('Your changes have been saved.');
            }
        }

        $sender->render($this->getView('configuration.php'));
    }

    /**
     * Add a link to the dashboard menu.
     *
     * @param $sender Sending controller instance.
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        $menu = $sender->EventArguments['SideMenu'];
        $menu->addLink('Add-ons', t('Keyword Blocker'), 'settings/keywordBlocker', 'Garden.Settings.Manage');
    }

    /**
     * Hook on CheckSpam event.
     *
     * Checks post cleanliness and send it for review if it is dirty.
     *
     * @param $sender Sending controller instance.
     * @param $args Event arguments.
     */
    public function base_checkSpam_handler($sender, $args) {

        $isPostClean = $this->isPostClean($args['RecordType'], $args['Data']);
        if (!$isPostClean) {
            $sender->EventArguments['IsSpam'] = true;

            // Use isSpam to stop the post from being posted and log it for moderation review.
            if (c('KeywordBlocker.BlockMode', 'Moderation') === 'Moderation') {
                $sender->EventArguments['Options'] = array_merge(
                    $sender->EventArguments['Options'],
                    array('Log' => false) // Do not log this post as Spam
                );
                LogModel::insert('Pending', $args['RecordType'], $args['Data']);
            }
        }
    }

    /**
     * Check if a post is clean from those nasty blocked words.
     *
     * @param $recordType Type of record being checked. (Comment, Discussion...)
     * @param $recordData Content of the record.
     * @return bool True if clean, false otherwise.
     */
    protected function isPostClean($recordType, $recordData) {
        $words = $this->getBlockedWords();

        foreach($words as $word) {

            $toTest = $recordData['Body'];
            if ($recordType === 'Discussion') {
                $toTest = $recordData['Name']."\n".$toTest;
            }

            if (preg_match('#\b'.preg_quote($word, '#').'\b#i', $toTest) === 1) {
                return false;
                break;
            }
        }

        return true;
    }

    /**
     * Get the list of blocked words
     *
     * @return array
     */
    protected function getBlockedWords() {
        static $words = null;

        if ($words === null) {
            $words = array();
            $wordsString = c('KeywordBlocker.Words', null);
            if ($wordsString !== null) {
                $explodedWords = explode(';', $wordsString);
                foreach ($explodedWords as &$word) {
                    $word = trim($word);
                }
                unset($word);
                $words = $explodedWords;
            }
        }

        return $words;
    }
}
