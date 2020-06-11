<?php
/**
 * @author Isis Graziatto <isis.g@vanillaforums.com>
 * @author Dani M <danim@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

use Vanilla\Contracts\ConfigurationInterface;

/**
 * Class PrivateDiscussionsPlugin
 *
 * Display restricted discussion view for guests.
 */
class PrivateDiscussionsPlugin extends Gdn_Plugin {

    /** @var string */
    const ADDON_PATH = 'plugins/privatediscussions';

    /** @var int */
    const WORDCOUNT_DEFAULT = 100;

    /** @var bool */
    const STRIPEMBEDS_DEFAULT = true;

    /** @var bool */
    const FEATURE_DISCUSSIONSITEMAPS_DEFAULT = true;

    /** @var Gdn_Session */
    private $session;

    /** @var int */
    private $wordCount;

    /** @var bool */
    private $stripEmbeds;

    /** @var ConfigurationInterface */
    private $config;

    /**
     * PrivateDiscussionsPlugin constructor.
     *
     * @param ConfigurationInterface $configuration
     * @param Gdn_Session $session
     */
    public function __construct(ConfigurationInterface $configuration, Gdn_Session $session) {
        parent::__construct();
        $this->config = $configuration;
        $this->session = $session;
        $this->wordCount = $this->config->get('Plugins.PrivateDiscussions.WordCount', self::WORDCOUNT_DEFAULT);
        $this->stripEmbeds = (bool) $this->config->get('Plugins.PrivateDiscussions.StripEmbeds', self::STRIPEMBEDS_DEFAULT);
    }

    /**
     * Run once on enable.
     *
     * @return void
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Run on utility/update.
     *
     * @return void
     */
    public function structure() {
        $this->config->set('Feature.discussionSiteMaps.Enabled', self::FEATURE_DISCUSSIONSITEMAPS_DEFAULT);
    }

    /**
     * Plugin settings page.
     *
     * @param SettingsController $sender
     */
    public function settingsController_privatediscussions_create(Gdn_Controller $sender) {
        $sender->permission('Garden.Settings.Manage');
        $sender->setData('Title', t('Private Discussions Settings'));

        if ($sender->Form->authenticatedPostBack()) {
            $sender->Form->validateRule(
                'Plugins.PrivateDiscussions.WordCount',
                'function:ValidateInteger',
                sprintf(t('%s is required'), 'Word Count')
            );
            $sender->Form->validateRule(
                'Plugins.PrivateDiscussions.WordCount',
                'function:ValidateRequired',
                sprintf(t('%s must be numeric'), 'Word Count')
            );
        }

        $configurationModule = new ConfigurationModule($sender);
        $configurationModule->initialize([
            'Plugins.PrivateDiscussions.WordCount' => [
                'LabelCode' => 'Word Count',
                'Description' => t('Truncate the initial discussion text to this many words.'),
                'Control' => 'TextBox',
                'Default' => self::WORDCOUNT_DEFAULT
            ],
            'Plugins.PrivateDiscussions.StripEmbeds' => [
                'LabelCode' => 'Strip Embeds',
                'Description' => t('Strip images and videos out of posts.'),
                'Control' => 'Toggle',
                'Default' => self::STRIPEMBEDS_DEFAULT
            ]
        ]);

        $configurationModule->renderAll();
    }

    /**
     * Checks if embeds needs to be stripped.
     *
     * @return bool
     */
    public function getStripEmbeds() {
        return $this->stripEmbeds;
    }

    /**
     * Returns the word count limit.
     *
     * @return int
     */
    public function getWordCount() {
        return $this->wordCount;
    }

    /**
     * Adds a dispatcher block exception for discussion page.
     *
     * @param Gdn_Dispatcher $sender
     * @param Gdn_Dispatcher $args
     */
    public function gdn_dispatcher_beforeBlockDetect_handler($sender, $args) {
        $sender->addBlockException('#^discussion(/)#', Gdn_Dispatcher::BLOCK_NEVER);
        $sender->addBlockException('#^robots(/|$|\.txt)#', Gdn_Dispatcher::BLOCK_NEVER);
    }

    /**
     * Massage the data and switch the view.
     *
     * @param DiscussionController $sender
     */
    public function discussionController_render_before($sender) {
        // guest has view permission
        if (!$this->session->isValid()) {
            if (!$sender->CategoryID) {
                redirectTo('/entry/signin');
            }
            $discussionBody = $sender->Data['Discussion']->Body;
            $discussionFormat = $sender->Data['Discussion']->Format;
            if (!$discussionBody || !$discussionFormat) {
                return;
            }
            if (isset($sender->Data['Comments'])) {
                unset($sender->Data['Comments']);
            }
            $data = Gdn::formatService()->renderHTML($discussionBody, $discussionFormat);
            $massagedData = $this->massageData($data);
            // set data back to the controller
            $sender->Data['Discussion']->Body = $massagedData;
            // unset panel guestModule
            unset($sender->Assets['Panel']['GuestModule']);
            // render view override
            Gdn_Theme::section('DiscussionRestricted');
            $sender->addCssFile('privatediscussions.css', self::ADDON_PATH . '/design');
            // Private Communities is enabled
            if ((bool)c('Garden.PrivateCommunity')) {
                $sender->Head->addTag('meta', ['name' => 'robots', 'content' => 'index,nofollow']);
            }
            $sender->View = $sender->fetchViewLocation('index', 'discussion', self::ADDON_PATH);
        }
    }

    /**
     * Override permissions to prevent guest to see comments
     */
    public function commentsApiController_getFilters() {
        if (!$this->session->isValid()) {
            throw new Exception(t('You must sign in to view comments.'), 403);
        }
    }

    /**
     * Massage the data.
     *
     * @param string $data The data string
     * @return string $data The Massaged data string
     */
    private function massageData(string $data) {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->loadHTML($data);
        if ($this->getStripEmbeds()) {
            $this->stripEmbeds($dom);
            $data = $this->stripImages($dom);
        }
        // trim to word count
        $data = $this->stripText($data, $dom, $this->getWordCount());
        return $data;
    }

    /**
     * Strip embeds from the data string.
     *
     * @param DOMDocument $dom
     * @return string Data without the embeds
     */
    private function stripEmbeds(DOMDocument $dom): string {
        $xpath = new DomXPath($dom);
        // embed classes.
        $embedClasses = ['js-embed', 'embedResponsive', 'embedExternal', 'embedImage', 'VideoWrap'];
        foreach ($embedClasses as $key => $value) {
            $xpathQuery = $xpath->query(".//*[contains(@class, '$embedClasses[$key]')]");
            $xpathDivQuery = $xpath->query("//div[@data-embedjson]");
            $dataClassItem = $xpathQuery->item(0);
            $dataDivItem = $xpathDivQuery->item(0);
            if ($dataClassItem) {
                $dataClassItem->parentNode->removeChild($dataClassItem);
            } elseif ($dataDivItem) {
                $dataDivItem->parentNode->removeChild($dataDivItem);
            }
        }
        $data = $dom->saveHTML();
        return $data;
    }

    /**
     * Strip images tags.
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
     * @param int $wordCount
     * @return string The minified text with its html tags.
     */
    private function stripText(string $data, DOMDocument $dom, int $wordCount): string {
        $dom->loadHTML(mb_convert_encoding("<div>{$data}</div>", "HTML-ENTITIES", "UTF-8"), LIBXML_HTML_NOIMPLIED);
        $this->stripTextRecursive($dom->documentElement, $wordCount);
        $minifiedText = substr($dom->saveHTML($dom->documentElement), 5, -6);
        return $minifiedText;
    }

    /**
     * Strip text recursively while preserving html format.
     *
     * @param mixed $element
     * @param int $limit
     * @return int Return limit used to count remaining tags.
     */
    private function stripTextRecursive($element, int $limit): int {
        if ($limit > 0) {
            // Nodetype text
            if ($element->nodeType == 3) {
                $limit -= str_word_count($element->data);
                if ($limit < 0) {
                    $element->nodeValue = substr($element->nodeValue, 0, str_word_count($element->data));
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
     * Override this function to return the Body as is since if it
     *
     * Event argument for $object will be 'Comment' or 'Discussion'.
     *
     * @param stdClass $object Comment or discussion.
     * @return string Parsed body.
     * @since 2.1
     */
    function formatBody($object) {
        Gdn::controller()->fireEvent('BeforeCommentBody');
        if (Gdn_Theme::inSection('DiscussionRestricted')) {
            $object->FormatBody = $object->Body;
        } else {
            $object->FormatBody = Gdn_Format::to($object->Body, $object->Format);
        }
        Gdn::controller()->fireEvent('AfterCommentFormat');

        return $object->FormatBody;
    }
}
