<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license proprietary
 */

use Garden\EventManager;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Formatting\FormatConfig;
use Vanilla\Formatting\Formats\TextFormat;
use Vanilla\Formatting\FormatService;
use Vanilla\Web\TwigRenderTrait;

/**
 * Class CatalogueDisplayPlugin
 *
 * Creates a "catalogue" style for viewing discussions.
 * This means that the first thumbnail in the Discussion is displayed when listed on the Recent Discussions or the Category page.
 */
class CatalogueDisplayPlugin extends Gdn_Plugin {
    use TwigRenderTrait;

    const CATEGORY_ONLY = true;
    const MASONRY_ENABLED = false;

    /**
     * @var EventManager
     */
    private $eventManager;
    /**
     * @var DiscussionModel
     */
    private $discussionModel;
    /**
     * @var CategoryModel
     */
    private $categoryModel;
    /**
     * @var FormatService
     */
    private $formatService;
    /**
     * @var FormatConfig
     */
    private $formatConfig;
    /**
     * @var Gdn_Locale
     */
    private $locale;
    /**
     * @var ConfigurationInterface
     */
    private $config;

    /**
     * CatalogueDisplayPlugin constructor.
     *
     * @param EventManager $eventManager
     * @param DiscussionModel $discussionModel
     * @param CategoryModel $categoryModel
     * @param FormatService $formatService
     * @param FormatConfig $formatConfig
     * @param Gdn_Locale $locale
     * @param ConfigurationInterface $config
     */
    public function __construct(
        EventManager $eventManager,
        DiscussionModel $discussionModel,
        CategoryModel $categoryModel,
        FormatService $formatService,
        FormatConfig $formatConfig,
        Gdn_Locale $locale,
        ConfigurationInterface $config
    ) {
        parent::__construct();
        $this->eventManager = $eventManager;
        $this->discussionModel = $discussionModel;
        $this->categoryModel = $categoryModel;
        $this->formatService = $formatService;
        $this->formatConfig = $formatConfig;
        $this->locale = $locale;
        $this->config = $config;
    }

    /**
     * Fires on Utility Update or when the plugin is turned on.
     *
     * @return void
     * @throws Exception If table and columns structure failed.
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Add columns to Category and Discussion tables to designate when we display a Discussion as a "catalogue".
     *
     * @throws Exception If table and columns structure failed.
     */
    public function structure() {
        Gdn::structure()
            ->table('Category')
            ->column('CatalogueDisplay', 'tinyint(1)', ['Null' => false, 'Default' => 0])
            ->set()
        ;

        Gdn::structure()
            ->table('Discussion')
            ->column('CatalogueDisplay', 'tinyint(1)', ['Null' => false, 'Default' => 0])
            ->set()
        ;
    }

    /**
     * Add CSS to handle the thumbnail in the list view, JS to handle the popoup.
     *
     * @param Gdn_Controller $sender
     */
    public function base_render_before(Gdn_Controller $sender) {
        if (is_object($sender->Head) && ($sender->ClassName == 'CategoriesController'
                || (!$this->config->get('CatalogueDisplay.OnlyOnCategory') && $sender->ClassName == 'DiscussionsController'))) {
            // include magnific-popup before catalogue-style so that catalogue style can override styles.
            $sender->addCssFile('magnific-popup.css', 'dashboard');
            if (!$this->config->get('CatalogueDisplay.Masonry.Enabled')) {
                $sender->addCssFile('catalogue-style.css', 'plugins/cataloguedisplay');
            }
            $sender->addJsFile('magnific-popup.min.js');
            $sender->Head->addString('<style>.CatalogueRow .ItemContent {min-height: '.$this->config->get('CatalogueDisplay.Thumbnail.Size', '70').'px}</style>');
        }
    }

    /**
     * When editing a category, if there are discussions in the category, update them to add or remove the "catalogue" style.
     *
     * @param CategoryModel $sender
     * @param array $args
     */
    public function categoryModel_beforeSaveCategory_handler(CategoryModel $sender, array $args) {
        $formPostValues = $args['FormPostValues'] ?? false;
        $categoryID = $args['CategoryID'] ?? false;
        $insert = $categoryID > 0 ? false : true;
        if ($sender->validate($formPostValues, $insert)) {
            $this->discussionModel->update(
                ['CatalogueDisplay' => $formPostValues['CatalogueDisplay']],
                ['CategoryID' => $categoryID]
            );
        }
    }

    /**
     * Add a toggle to the Add/Edit Category in the dashboard to designate this category as a "catalogue" style.
     *
     * @param VanillaSettingsController $sender
     * @param array $args
     */
    public function settingsController_addEditCategory_handler(VanillaSettingsController $sender, array $args) {
        $warningText = '';
        if (strcasecmp($this->formatConfig->getDefaultDesktopFormat(), TextFormat::FORMAT_KEY) === 0
            || strcasecmp($this->formatConfig->getDefaultMobileFormat(), TextFormat::FORMAT_KEY) === 0) {
            $warningText = ' <em>'.$this->locale->translate('You must have the Post and Mobile Formats set to anything but "Text" in the Advanced Editor Plugin.').'</em>';
        }
        $description = $this->locale->translate('Each discussion will show an uploaded image on the Discussions page instead of the author information. '
            .'This only applies to categories with "Discussions" as the "Display As.');
        $sender->Data['_ExtendedFields']['CatalogueDisplay'] = [
            'Name' => 'CatalogueDisplay',
            'Label' => 'Catalogue Style',
            'Control' => 'Toggle',
            'Description' => '<div class="Warning">'.$description.$warningText.'</div>',
        ];
    }

    /**
     * Create a form in the Dashboard for uploading, deleting and replacing a Placeholder thumbnail.
     *
     * @param SettingsController $sender
     * @param array $args
     * @throws Gdn_UserException If User has not the right permissions.
     */
    public function settingsController_catalogueDisplay_create(SettingsController $sender, array $args) {
        $sender->permission('Garden.Settings.Manage');
        $sender->setData('Title', $this->locale->translate('Upload Placeholder Image'));
        if ($sender->Form->authenticatedPostBack() === true) {
            if ($sender->Form->getFormValue('Photo', false) === '') {
                $sender->Form->removeFormValue('Photo');
            }
            $onlyOnCategory = $sender->Form->getValue('CatalogueDisplay.OnlyOnCategory');
            Gdn::config()->saveToConfig('CatalogueDisplay.OnlyOnCategory', $onlyOnCategory);
            $masonryEnabled = $sender->Form->getValue('CatalogueDisplay.Masonry.Enabled');
            Gdn::config()->saveToConfig('CatalogueDisplay.Masonry.Enabled', $masonryEnabled);
            $existingImage = $this->config->get('CatalogueDisplay.PlaceHolderImage');
            $request = Gdn::request();
            $tmpImageUrl = $request->post('Photo', null);

            // Update setting if we deleted the default placeholder
            if ($existingImage && empty($tmpImageUrl)) {
                Gdn::config()->saveToConfig(['CatalogueDisplay.PlaceHolderImage' => '']);
            }
            // Update setting if we set a default placeholder
            if (!empty($tmpImageUrl)) {
                Gdn::config()->saveToConfig(['CatalogueDisplay.PlaceHolderImage' => $tmpImageUrl]);
            }
        }
        $sender->Form->setValue('CatalogueDisplay.OnlyOnCategory', $this->config->get('CatalogueDisplay.OnlyOnCategory', self::CATEGORY_ONLY));
        $sender->Form->setValue('CatalogueDisplay.Masonry.Enabled', $this->config->get('CatalogueDisplay.Masonry.Enabled', self::MASONRY_ENABLED));
        $sender->Form->setValue('Photo', $this->config->get('CatalogueDisplay.PlaceHolderImage', null));
        $sender->render('settings', '', 'plugins/cataloguedisplay');
    }

    /**
     * When a discussion is being created in a category that is "catalogue" style, add the "CatalogueDisplay" to the discussion row.
     *
     * @param DiscussionModel $sender
     * @param array $args
     */
    public function discussionModel_beforeSaveDiscussion_handler(DiscussionModel $sender, array $args) {
        $category = $this->categoryModel->getWhere(
            [
                'CategoryID' => $args['FormPostValues']['CategoryID'],
                'CatalogueDisplay' => 1,
            ]
        )->firstRow()
        ;
        $args['FormPostValues']['CatalogueDisplay'] = ($category) ? 1 : 0;
    }

    /**
     * When saving a discussion, remove the thumbnail URL stored in cache if there is one.
     *
     * @param PostController $sender
     * @param array $args
     */
    public function postController_afterDiscussionSave_handler(PostController $sender, array $args) {
        if (isset($args['Discussion'])) {
            // Remove the cache entry iff we deleted the post
            $cacheKey = $this->makeThumbnailCacheKey($args['Discussion']->DiscussionID);
            Gdn::cache()->remove($cacheKey);
        }
    }

    /**
     * If the Discussions Layout is not table, echo out the thumbnail (or placeholder).
     *
     * @param DiscussionsController $sender
     * @param array $args
     */
    public function discussionsController_beforeDiscussionContent_handler(DiscussionsController $sender, array $args) {
        if ($this->config->get('Vanilla.Discussions.Layout') === 'table') {
            return;
        }
        if ($this->config->get('CatalogueDisplay.OnlyOnCategory')) {
            return;
        }
        $discussion = $args['Discussion'] ?? null;
        if ($discussion) {
            echo $this->displayCatalogueImage($discussion);
        }
    }

    /**
     * If the Discussions Layout is table, echo out the thumbnail (or placeholder).
     *
     * @param DiscussionController $sender
     * @param array $args
     */
    public function discussionsController_beforeDiscussionTitle_handler(DiscussionController $sender, array $args) {
        $discussion = $args['Discussion'] ?? null;
        if ($this->config->get('CatalogueDisplay.OnlyOnCategory')) {
            return;
        }
        if ($this->config->get('Vanilla.Discussions.Layout') === 'table' && $discussion) {
            echo $this->displayCatalogueImage($discussion);
        }
    }

    /**
     * If the Discussions Layout is not table, echo out the thumbnail (or placeholder).
     *
     * @param CategoriesController $sender
     * @param array $args
     */
    public function categoriesController_beforeDiscussionContent_handler(CategoriesController $sender, array $args) {
        $discussion = $args['Discussion'] ?? null;
        if ($this->config->get('Vanilla.Discussions.Layout') === 'table') {
            return;
        }
        if ($discussion) {
            echo $this->displayCatalogueImage($discussion);
        }
    }

    /**
     * If the Discussions Layout is table, echo out the thumbnail (or placeholder).
     *
     * @param CategoriesController $sender
     * @param array $args
     */
    public function categoriesController_beforeDiscussionTitle_handler(CategoriesController $sender, array $args) {
        $discussion = $args['Discussion'] ?? null;
        if ($this->config->get('Vanilla.Discussions.Layout') === 'table' && $discussion) {
            echo $this->displayCatalogueImage($discussion);
        }
    }

    /**
     * Add the CSS class to "catalogue" displayed discussions in the discussion view.
     *
     * @param DiscussionsController $sender
     * @param array $args
     */
    public function discussionsController_beforeDiscussionName_handler(DiscussionsController $sender, array $args) {
        if ($this->config->get('CatalogueDisplay.OnlyOnCategory')) {
            return;
        }
        $discussion = $args['Discussion']??null;
        if ($discussion && is_object($discussion) && $discussion->CatalogueDisplay) {
            $args['CssClass'] .= ' CatalogueRow';
        }
    }

    /**
     * Add the CSS class to "catalogue" displayed discussions in the categories view.
     *
     * @param CategoriesController $sender
     * @param array $args
     */
    public function categoriesController_beforeDiscussionName_handler(CategoriesController $sender, array $args) {
        $discussion = $args['Discussion']??null;
        if ($discussion && is_object($discussion) && $discussion->CatalogueDisplay) {
            $args['CssClass'] .= ' CatalogueRow';
        }
    }

    /**
     * If the discussion is in the "catalogue" style, get the first thumbnail and display it in the list view.
     *
     * @param object $discussion Discussion row.
     * @return null|string A photo tag, or a placeholder div to be displayed in place of a photo.
     */
    public function displayCatalogueImage($discussion) {
        $catalogueDisplay = $discussion->CatalogueDisplay ?? false;
        if (!$catalogueDisplay) {
            return;
        }

        $imgTag = null;
        $cssClassWrapper = [];
        $imgAttributes = ['class' => []];
        $catalogueImgURL = discussionUrl($discussion);
        if (!$this->config->get('CatalogueDisplay.Masonry.Enabled')) {
            $cssClassWrapper[] = 'catalogue-image-wrapper';
        }
        if (!$this->config->get('CatalogueDisplay.Masonry.Enabled')) {
            $imgAttributes['class'] = ['catalogue-image'];
        }

        // First extract image URL from inside the body.
        $imageUrl = $this->findImageUrl($discussion);

        // If there is no image, look for the Placeholder Image saved in the config.
        $placeHolderUrl = $this->config->get('CatalogueDisplay.PlaceHolderImage');
        if (!$imageUrl && $placeHolderUrl) {
            $imgAttributes['class'][] = 'placeholder-image';
            $imgAttributes['alt'] = $this->locale->translate('Placeholder');
            $imageUrl = $placeHolderUrl;
        }

        $eventArguments['Discussion'] = $discussion;
        $eventArguments['catalogueImgURL'] = &$catalogueImgURL;
        $eventArguments['cssClassWrapper'] = &$cssClassWrapper;
        $eventArguments['imgAttributes'] = &$imgAttributes;
        $this->eventManager->fire('beforeCatalogueDisplay', $eventArguments);

        return $this->renderTwig("/plugins/cataloguedisplay/views/catalogueImage.twig", [
            'placeHolderUrl' => $catalogueImgURL,
            'placeHolderImgUrl' => $imageUrl,
            'imgAttributes' => $imgAttributes,
            'cssClassWrapper' => $cssClassWrapper,
        ]);
    }

    /**
     * Takes a discussion and parses out the first image in the discussion.
     *
     * @param object $discussion Discussion to have image found based on its formatting.
     * @return mixed|string URI of the first image in the Discussion.
     */
    public function findImageUrl($discussion) {
        // Get the image URL from cache.
        $cacheKey = $this->makeThumbnailCacheKey($discussion->DiscussionID);
        $imageUrl = Gdn::cache()->get($cacheKey);
        if (!$imageUrl || $imageUrl === Gdn_Cache::CACHEOP_FAILURE) {
            // If no image URL is cached, parse it from the DOM.
            /** @var string|null $imageUrl First image URL from the discussion body */
            $imageUrl = $this->formatService->parseImageUrls($discussion->Body, $discussion->Format)[0] ?? null;
            if ($imageUrl) {
                Gdn::cache()->store($cacheKey, $imageUrl);
            }
        }

        // if not get the image URL using pQuery and cache the results
        return $imageUrl;
    }

    /**
     * Get a cache key from the discussion ID
     *
     * @param int $discussionID
     * @return string
     */
    public function makeThumbnailCacheKey(int $discussionID): string {
        // Create the image URL from cache.
        return 'catalogueDisplay.thumbnailURL.'.$discussionID;
    }

    /**
     * Implement masonry script and css
     *
     * @param CategoriesController $sender
     */
    public function categoriesController_render_before(CategoriesController $sender) {
        if ($this->config->get('Vanilla.Discussions.Layout') === 'table') {
            return;
        }
        if ($this->config->get('CatalogueDisplay.Masonry.Enabled')) {
            $sender->addJsFile('library/jQuery-Masonry/jquery.masonry.js', 'plugins/Reactions');
            $sender->addJsFile('masonry-categories.js', 'plugins/cataloguedisplay');
            $sender->addCssFile('catalogue-masonry.css', 'plugins/cataloguedisplay');
        }
    }
}
