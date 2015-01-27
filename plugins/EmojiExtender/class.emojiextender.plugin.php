<?php if (!defined('APPLICATION')) exit;

$PluginInfo['EmojiExtender'] = array(
    'Name'        => "Emoji Extender",
    'Description' => "Change your emoji set!",
    'Version'     => '1.0.0',
    'Author'      => "Becky Van Bussel",
    'AuthorEmail' => 'rvanbussel@vanillaforums.com',
    'AuthorUrl'   => 'http://vanillaforums.com',
    'License'     => 'Proprietary',
    'SettingsUrl' => '/settings/EmojiExtender'
);

/**
 * Emoji Extender Plugin
 *
 * @author    Becky Van Bussel <rvanbussel@vanillaforums.com>
 * @copyright 2014 (c) Becky Van Bussel
 * @license   Proprietary
 * @package   EmojiExtender
 * @since     1.0.0
 *
 * Users can change or delete emoji sets for their forums.
 *
 */

class EmojiExtenderPlugin extends Gdn_Plugin {

    /**
    * Name of emoji set.
    *
    * @var string
    */
//    protected $emojiSet = '';

    /**
    * Indicated whether to merge chosen set with default set.
    *
    * @var boolean
    */
//    protected $merge = false;

    /**
    * Path to folder containing emoji set.
    *
    * @var string
    */
//    protected $emojiPath = '/resources/emoji/default';

    /**
     * @var string The path to vanilla repo.
     */
//    protected $rootPath = '/var/www/internal';

    /**
    * List of all available emoji sets.
    *
    * @var array
    */
    protected $emojiSets;


    /**
    * Setup some variables and change emoji set.
    */
    public function __construct() {
        parent::__construct();

        $root = '/plugins/EmojiExtender/emoji';

        $this->emojiSets = array(
            ''        => array('name' => 'Apple Emoji', 'icon' => "$root/default.png", 'path' => '/resources/emoji'),
            'twitter' => array('name' => 'Twitter Emoji', 'icon' => "$root/twitter/twitter-icon.png", 'path' => PATH_ROOT."$root/twitter"),
            'little'  => array('name' => 'Little Emoji', 'icon' => "$root/little/little-icon.png", 'path' => PATH_ROOT."$root/little"),
            'rice'    => array('name' => 'Riceball Emoticons', 'icon' => "$root/rice/rice-icon.png", 'path' => PATH_ROOT."$root/rice"),
            'yahoo'   => array('name' => 'Yahoo Chat', 'icon' => "$root/yahoo/yahoo-icon.png", 'path' => PATH_ROOT."$root/yahoo"),
            'none'    => array('name' => T('No Emoji'), 'icon' => "$root/none/none-icon.png", 'path' => PATH_ROOT."$root/none"),
        );
//        $this->pluginInfo = Gdn::PluginManager()->GetPluginInfo('EmojiExtender', Gdn_PluginManager::ACCESS_PLUGINNAME);
//        $this->emojiSet = C('Garden.EmojiSet', '');
//        $this->emojiPath = $this->emojiSets[$this->emojiSet]['path'];
        //If ever you want the functionality to merge the custom emoji set with the default set, uncomment below
        //$this->merge = C('Plugins.EmojiExtender.merge', false);
    }


    /**
     * Change the emoji set used, either by merging or or overriding the default set.
     *
     * @param Emoji $emoji The emoji object to change.
     * @param string $emojiSetName The name of the emoji set to enable.
     */
    public function changeEmojiSet($emoji, $emojiSetName) {
        if (!array_key_exists($emojiSetName, $this->emojiSets)) {
            trigger_error("Emoji set not found: $emojiSetName.", E_USER_NOTICE);
            return;
        }

        // First grab the manifest to the emoji.
        $emojiSet = $this->emojiSets[$emojiSetName];
        $manifestPath = $emojiSet['path'].'/manifest.php';
        if (!file_exists($manifestPath)) {
            trigger_error("Emoji manifest does not exist: $manifestPath.", E_USER_NOTICE);
            return;
        }
        try {
            $manifest = require $manifestPath;
        } catch (Exception $ex) {
            trigger_error($ex->getMessage(), E_USER_NOTICE);
            return;
        }

        $emoji->setFromManifest($manifest, StringBeginsWith($emojiSet['path'], PATH_ROOT, true, true));
    }

    /**
    *
    * EVENT HANDLERS
    *
    */

    /**
    * Subscribe to event in Emoji class instance method.
    *
    * @param Emoji $sender
    * @param Args $args
    */
    public function Emoji_Init_Handler($sender, $args) {
        $emojiSetName = C('Garden.EmojiSet');
        if (!$emojiSetName) {
            return;
        }
        $this->changeEmojiSet($sender, $emojiSetName);
    }

    /**
    * Configure settings page in dashboard.
    *
    * @param SettingsController $sender
    * @param array $args
    */
    public function SettingsController_EmojiExtender_Create($sender, $args) {
        $cf = new ConfigurationModule($sender);

        $items = array();

        foreach ($this->emojiSets as $key => $emoji) {
            $manifestPath = $emoji['path'].'/manifest.php';
            if (file_exists($manifestPath)) {
                $manifest = require $manifestPath;
            } else {
                $manifest = array(
                    'name' => 'Apple Emoji',
                    'author' => 'Apple Inc.',
                    'description' => 'A modern set of emoji you might recognize from any of your ubiquitous iDevices.'
                );
            }

            $items[$key] = '@'.Img($emoji['icon'], array('alt' => $emoji['name'])).
            '<div emojiset-body>'.
                '<div><b>'.htmlspecialchars($manifest['name']).'</b></div>'.
                (empty($manifest['author']) ? '' : '<div class="emojiset-author">'.sprintf(T('by %s'), $manifest['author']).'</div>').
                (empty($manifest['description']) ? '' : '<p class="emojiset-description">'.Gdn_Format::Wysiwyg($manifest['description']).'</p>').
            '</div>';
        }
        $cf->Initialize(array(
            'Garden.EmojiSet' => array(
                'LabelCode' => 'Emoji Set',
                'Control' => 'radiolist',
                'Description' => '<p>Which emoji set would you like to use?</p>',
                'Items' => $items,
                'Options' => array('list' => true, 'listclass' => 'emojiext-list', 'display' => 'after')
            ),
            //If ever you want the functionality to merge the custom emoji set with the default set, uncomment below
            //'Plugins.EmojiExtender.merge' => array('LabelCode' => 'Merge set', 'Control' => 'Checkbox', 'Description' => '<p>Would you like to merge the selected emoji set with the default set?</p> <p><small><strong>Note:</strong> Some emojis in the default set may not be represented in the selected set and vice-versa.</small></p>'),
        ));

        $sender->AddCssFile('settings.css', 'plugins/EmojiExtender');
        $sender->AddSideMenu();
        $sender->SetData('Title', sprintf(T('%s Settings'), 'Emoji'));
        $cf->RenderAll();
   }

    /**
    * Replace emoticons in comment preview.
    *
    * @param PostController $Sender
    */
//    public function PostController_AfterCommentPreviewFormat_Handler($Sender) {
//        if (Emoji::instance()->enabled) {
//            $Sender->Comment->Body = Emoji::instance()->translateToHtml($Sender->Comment->Body);
//        }
//    }

    /**
    * Replace emoticons in comments.
    *
    * @param Base $Sender
    */
//    public function Base_AfterCommentFormat_Handler($Sender) {
//        if (Emoji::instance()->enabled) {
//             $Object = $Sender->EventArguments['Object'];
//             $Object->FormatBody = Emoji::instance()->translateToHtml($Object->FormatBody);
//             $Sender->EventArguments['Object'] = $Object;
//        }
//    }
}
