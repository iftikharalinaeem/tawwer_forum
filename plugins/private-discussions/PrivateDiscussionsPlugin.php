<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Plugins\PrivateDiscussions;

use Vanilla\Contracts\ConfigurationInterface;
use \DOMDocument;

/**
 * Class PrivateDiscussionsPlugin
 *
 * Display restricted discussion for guests.
 */
class PrivateDiscussionsPlugin extends \Gdn_Plugin {

    /** @var string */
    const ADDON_PATH = 'plugins/private-discussions';

    /** @var int */
    const WORDCOUNT_DEFAULT = 100;

    /** @var bool */
    const STRIPEMBEDS_DEFAULT = true;

    /**
     * PrivateDiscussionsPlugin constructor.
     * @param ConfigurationInterface $configuration
     */
    public function __construct(ConfigurationInterface $configuration) {
        parent::__construct();
        $this->config = $configuration;
    }

    /**
     * Return StripEmbeds
     * @return mixed
     */
    public function getStripEmbeds() {
        return (bool)$this->config->get('Plugins.PrivateDiscussions.StripEmbeds', self::STRIPEMBEDS_DEFAULT);
    }

    /**
     * Return WordCount
     *
     * @return mixed
     */
    public function getWordCount() {
        return $this->config->get('Plugins.PrivateDiscussions.WordCount', self::WORDCOUNT_DEFAULT);
    }

    /**
     * Plugin settings page.
     *
     * @param \SettingsController $sender
     */
    public function settingsController_privatediscussions_create(\Gdn_Controller $sender) {
        $sender->permission('Garden.Settings.Manage');
        $sender->setData('Title', t('Private Discussions Settings'));

        if ($sender->Form->authenticatedPostBack()) {
            $sender->Form->validateRule('Plugins.PrivateDiscussions.WordCount', 'function:ValidateInteger', 'Word Count must be numeric');
            $sender->Form->validateRule('Plugins.PrivateDiscussions.WordCount', 'function:ValidateRequired', 'Word Count is required');
        }

        $configurationModule = new \ConfigurationModule($sender);
        $configurationModule->initialize([
            'Plugins.PrivateDiscussions.StripEmbeds' => [
                'LabelCode' => 'Strip Embeds',
                'Control' => 'Toggle',
                'Default' => self::STRIPEMBEDS_DEFAULT
            ],
            'Plugins.PrivateDiscussions.WordCount' => [
                'LabelCode' => 'Word Count',
                'Control' => 'TextBox',
                'Default' => self::WORDCOUNT_DEFAULT
            ]
        ]);

        $configurationModule->renderAll();
    }

    /**
     * Adds a dispatcher block exception for discussion page.
     *
     * @param \Gdn_Dispatcher $sender
     * @param \ Gdn_Dispatcher $args
     */
    public function gdn_Dispatcher_beforeBlockDetect_handler($sender, $args) {
        $args['BlockExceptions']['#^discussion(/)#']  = \Gdn_Dispatcher::BLOCK_NEVER;
    }

    /**
     * Massage the data and switch the view.
     *
     * @param $sender
     */
    public function discussionController_render_before($sender) {
        if (!$sender->CategoryID) {
            redirectTo('/entry/signin');
        }
        $canViewCategory = \Gdn::session()->checkPermission('Vanilla.Discussions.View', true, 'Category', $sender->CategroyID);
        // return if the user is signed in or cannot view the category.
        if ((int)\Gdn::session()->isValid() || !$canViewCategory) {
            return;
        }

        //$userID = \Gdn::session()->UserID;
        $data = \Gdn::formatService()->renderHTML($sender->Data['Discussion']->Body, \Vanilla\Formatting\Formats\HtmlFormat::FORMAT_KEY);
        if ($this->getStripEmbeds()) {
            $this->stripImages($data);
        }

        //unset panel modules
        $sender->Assets['Panel'] = [];

        //render view override
        $sender->addCssFile('privatediscussions.css', self::ADDON_PATH.'/design');
        $sender->View = $sender->fetchViewLocation('index', 'discussion', self::ADDON_PATH);
    }
}
