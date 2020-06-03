<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Plugins\PrivateDiscussions;

use Vanilla\Contracts\ConfigurationInterface;
use \DOMDocument;
use \DOMXPath;

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

    /** @var \Gdn_Session */
    private $session;

    /**
     * PrivateDiscussionsPlugin constructor.
     *
     * @param ConfigurationInterface $configuration
     * @param \Gdn_Session $session
     */
    public function __construct(ConfigurationInterface $configuration, \Gdn_Session $session) {
        parent::__construct();
        $this->config = $configuration;
        $this->session = $session;
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
    public function gdn_dispatcher_beforeBlockDetect_handler($sender, $args) {
        $args['BlockExceptions']['#^discussion(/)#']  = \Gdn_Dispatcher::BLOCK_NEVER;
    }

    /**
     * Massage the data and switch the view.
     *
     * @param \DiscussionController $sender
     */
    public function discussionController_render_before($sender) {
        $canViewCategory = $this->session->checkPermission('Vanilla.Discussions.View', true, 'Category', $sender->CategroyID);
        // private communities is enable but guest has view permission
        if (!$this->session->isValid() && $canViewCategory && (bool)c('Garden.PrivateCommunity')) {
            if (!$sender->CategoryID) {
                redirectTo('/entry/signin');
            }
            $discussionBody = $sender->Data['Discussion']->Body;
            $discussionFormat = $sender->Data['Discussion']->Format;
            if (!$discussionBody || !$discussionFormat) {
                return;
            }
            $data = \Gdn::formatService()->renderHTML($discussionBody, $discussionFormat);
    //        if ($this->getStripEmbeds()) {
    //            $data = $this->stripEmbeds($data);
    //            //send to the view.
    //        }

            //unset panel modules
            $sender->Assets['Panel'] = [];

            //render view override
            $sender->addCssFile('privatediscussions.css', self::ADDON_PATH.'/design');
            $sender->View = $sender->fetchViewLocation('index', 'discussion', self::ADDON_PATH);
        }
    }

    /**
     * Strip embeds from the data string.
     *
     * @param string $data
     * @return string Massaged data
     */
    private function stripEmbeds(string $data) {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->loadHTML($data);
        $xpath = new DomXPath($dom);
        $classname='embedExternal embedImage';
        $xpath_results = $xpath->query(".//*[contains(@class, '$classname')]");

        if ($table = $xpath_results->item(0)) {
            $table ->parentNode->removeChild($table);
            $str = $dom->saveHTML();
            $data = $str;
        }
        return $data;
    }
}
