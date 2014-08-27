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
    protected $emojiSet = 'default';

    /**
    * Indicated whether to merge chosen set with default set.
    *
    * @var boolean
    */
    protected $merge = false;

    /**
    * Path to folder containing emoji set.
    *
    * @var string
    */
    protected $emojiPath = '/resources/emoji/default';

    /**
     * @var string The path to vanilla repo.
     */
    protected $rootPath = '/var/www/internal';

    /**
    * List of all available emoji sets.
    *
    * @var array
    */
    protected $emojiSets = array('default' => array('name' => 'Default Set',
                                                    'path' => '/resources/emoji'),
                                 'yahoo'   => array('name' => 'Yahoo Gifs',
                                                    'path' => '/plugins/EmojiExtender/design/images/emoji/yahoo'),
                                 'rice'    => array('name' => 'Rice',
                                                    'path' => '/plugins/EmojiExtender/design/images/emoji/rice'),
                                 'none'    => array('name' => 'No Emoji',
                                                    'path' => '/plugins/EmojiExtender/design/images/emoji/none')
                                 );


    /**
    * Setup some variables and change emoji set.
    */
    public function __construct() {
        parent::__construct();
        $this->pluginInfo = Gdn::PluginManager()->GetPluginInfo('EmojiExtender', Gdn_PluginManager::ACCESS_PLUGINNAME);
        $this->emojiSet = C('Plugins.EmojiExtender.emojiSet', 'default');
        $this->emojiPath = $this->emojiSets[$this->emojiSet]['path'];
        //If ever you want the functionality to merge the custom emoji set with the default set, uncomment below
        //$this->merge = C('Plugins.EmojiExtender.merge', false);
    }


    /**
    * Change the emoji set used, either by merging or or overriding the default set.
    */
    public function changeEmojiSet($emoji) {
        $root = ($this->emojiSet==='default') ? '/var/www/vanilla' : $this->rootPath;
        $jsonString = file_get_contents($root.$this->emojiPath.'/manifest.json');
        $this->emojiSets[$this->emojiSet] = array_merge($this->emojiSets[$this->emojiSet], json_decode($jsonString, true));
        if ($this->emojiSet == 'none') {
            $emoji->enabled = false;
        }
        else {
            $emoji->enabled = true;

            if ($this->merge) {
                $emoji->mergeOriginals(true); //setter method
                $emoji->setAssetPath($this->emojiPath);
                $emoji->mergeAdditionalEmoji($this->emojiSets[$this->emojiSet]['emoji']);
            }
            else {
                $emoji->setAssetPath($this->emojiPath);
                $emoji->setEmoji($this->emojiSets[$this->emojiSet]['emoji']);
                $emoji->updateAliasList(); //remove aliases that are not attached to emoji
            }

            $emoji->setEditorList($this->emojiSets[$this->emojiSet]['editor_list']);
            $emoji->addEmojiToDefinitionList(); //update suggester flyout
        }
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
        $this->changeEmojiSet($sender);
    }

    /**
    * Configure settings page in dashboard.
    *
    * @param SettingsController $sender
    * @param array $args
    */
    public function SettingsController_EmojiExtender_Create($sender, $args) {

        $cf = new ConfigurationModule($sender);

        $names = array();

        foreach ($this->emojiSets as $emoji) {
            array_push($names, $emoji['name']);
        }

        $emojiSets = array_combine(array_keys($this->emojiSets), array_keys($this->emojiSets));

        $cf->Initialize(array(
            'Plugins.EmojiExtender.emojiSet' => array(  'LabelCode' => 'Emoji Set',
                                                        'Control' => 'radiolist',
                                                        'Description' => '<p>Which emoji set would you like to use?</p>',
                                                        'Items' => $emojiSets ),
            //If ever you want the functionality to merge the custom emoji set with the default set, uncomment below
            //'Plugins.EmojiExtender.merge' => array('LabelCode' => 'Merge set', 'Control' => 'Checkbox', 'Description' => '<p>Would you like to merge the selected emoji set with the default set?</p> <p><small><strong>Note:</strong> Some emojis in the default set may not be represented in the selected set and vice-versa.</small></p>'),
        ));

        $sender->AddCssFile('settings.css', 'plugins/EmojiExtender');
        $sender->AddSideMenu();
        $sender->SetData('Title', T('Emoji Settings'));
        $cf->RenderAll();
   }

    /**
    * Replace emoticons in comment preview.
    *
    * @param PostController $Sender
    */
    public function PostController_AfterCommentPreviewFormat_Handler($Sender) {
        if (Emoji::instance()->enabled) {
            $Sender->Comment->Body = Emoji::instance()->translateToHtml($Sender->Comment->Body);
        }
    }

    /**
    * Replace emoticons in comments.
    *
    * @param Base $Sender
    */
    public function Base_AfterCommentFormat_Handler($Sender) {
        if (Emoji::instance()->enabled) {
             $Object = $Sender->EventArguments['Object'];
             $Object->FormatBody = Emoji::instance()->translateToHtml($Object->FormatBody);
             $Sender->EventArguments['Object'] = $Object;
        }
    }

    /**
    * Load CSS into head for suggester flyout in editor
    */
    public function AssetModel_StyleCss_Handler($Sender) {
        $Sender->AddCssFile('suggester.css', 'plugins/EmojiExtender');
   }
}
