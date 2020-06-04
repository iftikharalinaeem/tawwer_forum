<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Plugins\PrivateDiscussions;

use stdClass;
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
            $massagedData = $this->massageData($data);
            // set data back to the controller
            $sender->Data['Discussion']->Body = $massagedData;
            // unset panel modules
            $sender->Assets['Panel'] = [];
            // render view override
            $sender->addCssFile('privatediscussions.css', self::ADDON_PATH.'/design');
            $sender->View = $sender->fetchViewLocation('index', 'discussion', self::ADDON_PATH);
        }
    }

    /**
     * Massage the data.
     *
     * @param string $data
     */
    private function massageData(string $data) {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        @$dom->loadHTML($data);
        if ($this->getStripEmbeds()) {
            $this->stripEmbeds($dom);
        }
        // remove images when using advanced editor.
        $data = $this->stripImages($dom);
        // trim to word count
        $data = $this->stripText($data, $dom);
        return $data;
    }

    /**
     * Strip embeds from the data string.
     *
     * @param DOMDocument $dom
     * @return string Massaged data
     */
    private function stripEmbeds(DOMDocument $dom) :string {
        $xpath = new DomXPath($dom);
        // embed classes.
        $embedClasses = ['embedExternal embedImage', 'js-embed embedResponsive'];
        foreach($embedClasses as $embedClass) {
            $xpathQuery = $xpath->query(".//*[contains(@class, '$embedClass')]");
            if ($dataItem = $xpathQuery->item(0)) {
                $dataItem->parentNode->removeChild($dataItem);
            }
        }
        $data = $dom->saveHTML();
        return $data;
    }

    /**
     * Strip images.
     *
     * @param DOMDocument $dom
     * @return string Data stripped of images.
     */
    private function stripImages(DOMDocument $dom) {
        $domImages = $dom->getElementsByTagName('img');
        $imagesArray = [];
        foreach ($domImages as $domImage) {
            $imagesArray[] = $domImage;
        }
        foreach ($imagesArray as $domImage) {
            $domImage->parentNode->removeChild($domImage);
        }
        $data = $dom->saveHTML();
        return  $data;
    }

    /**
     * Prepare the html string.
     *
     * @param string $data
     * @param DOMDocument $dom
     * @return string The minified string with its html tags.
     */
    private function stripText(string $data, DOMDocument $dom) :string {
        $limit = $this->getWordCount();
        $dom->loadHTML(mb_convert_encoding("<div>{$data}</div>", "HTML-ENTITIES", "UTF-8"), LIBXML_HTML_NOIMPLIED);
        $this->stripTextRecursive($dom->documentElement, $limit);
        $minifiedDiscussion = substr($dom->saveHTML($dom->documentElement), 5, -6);
        return $minifiedDiscussion;
    }

    /**
     * Strip text recursively while ignoring html tags.
     *
     * @param mixed $element
     * @param int $limit
     * @return int Return limit used to count remaining tags.
     */
    private function stripTextRecursive($element, int $limit) :int {
        if ($limit > 0) {
            // Nodetype text
            if ($element->nodeType == 3) {
                $limit -= strlen($element->nodeValue);
                if ($limit < 0) {
                    $element->nodeValue = substr($element->nodeValue, 0, strlen($element->nodeValue) + $limit);
                }
            } else {
                for ($i = 0; $i < $element->childNodes->length; $i++) {
                    if ($limit > 0) {
                        $limit = $this->stripTextrecursive($element->childNodes->item($i), $limit);
                    } else {
                        $element->removeChild($element->childNodes->item($i));
                        $i--;
                    }
                }
            }
        }
        return $limit;
    }
}

if (!function_exists('formatBody')) {
    /**
     * Override this function to return the Body as is since it has already been formatted
     *
     * Event argument for $object will be 'Comment' or 'Discussion'.
     *
     * @param stdClass $object Comment or discussion.
     * @return string Parsed body.
     * @since 2.1
     */
    function formatBody($object) {
        \Gdn::controller()->fireEvent('BeforeCommentBody');
        $object->FormatBody = $object->Body;
        \Gdn::controller()->fireEvent('AfterCommentFormat');

        return $object->FormatBody;
    }
}
