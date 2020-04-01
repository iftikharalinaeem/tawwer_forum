<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license proprietary
 */

use Garden\EventManager;
use Vanilla\Web\TwigRenderTrait;


/**
 * Class CatalogueDisplayPlugin
 *
 * Creates a "catalogue" style for viewing discussions.
 * This means that the first thumbnail in the Discussion is displayed when listed on the Recent Discussions or the Category page.
 */
class CatalogueDisplayPlugin extends Gdn_Plugin {

    use TwigRenderTrait;

    const DEFAULT_USE_ONLY_ON_CATEGORY = true;
    const DEFAULT_MASONRY_ENABLED = false;

    /**
     * @var EventManager
     */
    private $eventManager;

    public function __construct(EventManager $eventManager) {
        parent::__construct();;
        $this->eventManager = $eventManager;
    }

    /**
     * Fires on Utility Update or when the plugin is turned on.
     *
     * @return bool|void
     * @throws Exception
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Add columns to Category and Discussion tables to designate when we display a Discussion as a "catalogue".
     *
     * @throws Exception
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
     * @param $sender Object
     */
    public function base_render_before($sender) {
        if (is_object($sender->Head) && ($sender->ClassName == 'CategoriesController'
                || (!c('CatalogueDisplay.OnlyOnCategory') && $sender->ClassName == 'DiscussionsController'))) {
            // include magnific-popup before catalogue-style so that catalogue style can override styles.
            $sender->addCssFile('magnific-popup.css', 'dashboard');
            if (!c('CatalogueDisplay.Masonry.Enabled')) {
                $sender->addCssFile('catalogue-style.css', 'plugins/cataloguedisplay');
            }
            $sender->addJsFile('magnific-popup.min.js');
            $sender->Head->addString('<style>.CatalogueRow .ItemContent {min-height: '.c('CatalogueDisplay.Thumbnail.Size', '70').'px}</style>');
        }
    }

    /**
     * When editing a category, if there are discussions in the category, update them to add or remove the "catalogue" style.
     *
     * @param CategoryModel Object $sender
     * @param CategoryModel Array $args
     */
    public function categoryModel_beforeSaveCategory_handler(CategoryModel $sender, $args) {
        $formPostValues = val('FormPostValues', $args);
        $categoryID = val('CategoryID', $args);
        $insert = val('CategoryID', $args) > 0 ? false : true;
        if ($sender->validate($formPostValues, $insert)) {
            $discussionModel = new DiscussionModel();
            $discussionModel->update(['CatalogueDisplay' => val('CatalogueDisplay', $formPostValues)], ['CategoryID' => $categoryID]);
        }
    }

    /**
     * Add a toggle to the Add/Edit Category in the dashboard to designate this category as a "catalogue" style.
     *
     * @param SettingsController Object $sender
     * @param SettingsController Array $args
     */
    public function settingsController_addEditCategory_handler(SettingsController $sender, $args) {
        $warningText = '';
        if (c('Garden.InputFormatter') === 'Text' || c('Garden.MobileInputFormatter') === 'Text') {
            $warningText = ' <em>You must have the Post and Mobile Formats set to anything but "Text" in the Advanced Editor Plugin.</em>';
        }
        $sender->Data['_ExtendedFields']['CatalogueDisplay'] = ['Name' => 'CatalogueDisplay', 'Label' => 'Catalogue Style', 'Control' => 'Toggle', 'Description' => '<div class="Warning">Each discussion will show an uploaded image on the Discussions page instead of the author information. This only applies to catagories with "Discussions" as the "Display As".'.$warningText.'</div>'];
    }

    /**
     * Create a form in the Dashboard for uploading, deleting and replacing a Placeholder thumbnail.
     *
     * @param SettingsController $sender
     * @param array $args
     * @throws Exception
     */
    public function settingsController_catalogueDisplay_create(SettingsController $sender, $args) {
        $sender->permission('Garden.Settings.Manage');
        $sender->setData('Title', t('Upload Placeholder Image'));
        if ($sender->Form->authenticatedPostBack() === true) {

            if ($sender->Form->getFormValue('Photo', false) === '') {
                $sender->Form->removeFormValue('Photo');
            }
            $onlyOnCategory = $sender->Form->getValue('CatalogueDisplay.OnlyOnCategory');
            Gdn::config()->Gdn::config()->saveToConfig('CatalogueDisplay.OnlyOnCategory', $onlyOnCategory);
            $masonryEnabled = $sender->Form->getValue('CatalogueDisplay.Masonry.Enabled');
            Gdn::config()->Gdn::config()->saveToConfig('CatalogueDisplay.Masonry.Enabled', $masonryEnabled);

            try {
                // Upload image
                $uploadImage = new Gdn_UploadImage();

                $existingImage = c('CatalogueDisplay.PlaceHolderImage');

                // Validate the upload
                $tmpImage = $uploadImage->validateUpload('Photo', false);

                if ($tmpImage) {
                    // Generate the target image name.
                    $targetImage = $uploadImage->generateTargetName(PATH_UPLOADS, '', true);
                    $basename = pathinfo($targetImage, PATHINFO_BASENAME);

                    // Delete any previously uploaded image.
                    if ($existingImage) {
                        $uploadImage->delete($existingImage);
                    }

                    // Save the uploaded image
                    $props = $uploadImage->saveImageAs(
                        $tmpImage,
                        $basename,
                        c('CatalogueDisplay.PlaceHolderImage', c('Garden.Thumbnail.Size', 100)),
                        c('CatalogueDisplay.PlaceHolderImage', c('Garden.Thumbnail.Size', 100))
                    );
                    Gdn::config()->saveToConfig(['CatalogueDisplay.PlaceHolderImage' => val('Url', $props)]);
                }

                if ($existingImage && Gdn::request()->post('Delete')) {
                    $uploadImage->delete($existingImage);
                    if (Gdn::request()->post('Delete')) {
                        Gdn::config()->saveToConfig(['CatalogueDisplay.PlaceHolderImage' => '']);
                    }
                }
            } catch (Exception $ex) {
                // Upload was optional so be quiet.
                throw $ex;
            }
        }
        $sender->Form->setValue('CatalogueDisplay.OnlyOnCategory', c('CatalogueDisplay.OnlyOnCategory', self::DEFAULT_USE_ONLY_ON_CATEGORY));
        $sender->Form->setValue('CatalogueDisplay.Masonry.Enabled', c('CatalogueDisplay.Masonry.Enabled', self::DEFAULT_MASONRY_ENABLED));
        $placeholderImg = !empty(c('CatalogueDisplay.PlaceHolderImage')) ? img(c('CatalogueDisplay.PlaceHolderImage')) : null;
        $sender->setData('PlaceholderImage', $placeholderImg);
        $sender->render('settings', '', 'plugins/cataloguedisplay');
    }

    /**
     * When a discussion is being created in a category that is "catalogue" style, add the "CatalogueDisplay" to the discussion row.
     *
     * @param DiscussionModel $sender
     * @param array $args
     */
    public function discussionModel_beforeSaveDiscussion_handler(DiscussionModel $sender, $args) {
        $categoryModel = new CategoryModel();
        $category = $categoryModel->getWhere(['CategoryID' => valr('FormPostValues.CategoryID', $args), 'CatalogueDisplay' => 1])->firstRow();
        $args['FormPostValues']['CatalogueDisplay'] = ($category) ? 1 : 0;
    }

    /**
     * When saving a discussion, remove the thumbnail URL stored in cache if there is one.
     *
     * @param PostController Object $sender
     * @param PostController Array $args
     */
    public function postController_afterDiscussionSave_handler($sender, $args) {
        $cacheKey = 'catalogueDisplay.thumbnailURL.'.valr('Discussion.DiscussionID', $args);
        Gdn::cache()->remove($cacheKey);
    }

    /**
     * If the Discussions Layout is not table, echo out the thumbnail (or placeholder).
     *
     * @param DiscussionController Object $sender
     * @param DiscussionsController Array $args
     */
    public function discussionsController_beforeDiscussionContent_handler($sender, $args) {
        if (c('Vanilla.Discussions.Layout') === 'table') {
            return;
        }
        if (c('CatalogueDisplay.OnlyOnCategory')) {
            return;
        }
        echo $this->displayCatalogueImage(val('Discussion', $args));
    }

    /**
     * If the Discussions Layout is table, echo out the thumbnail (or placeholder).
     *
     * @param DiscussionController Object $sender
     * @param DiscussionsController Array $args
     */
    public function discussionsController_BeforeDiscussionTitle_handler($sender, $args) {
        if (c('CatalogueDisplay.OnlyOnCategory')) {
            return;
        }
        if (c('Vanilla.Discussions.Layout') === 'table') {
            echo $this->displayCatalogueImage(val('Discussion', $args));
        }
    }

    /**
     * If the Discussions Layout is not table, echo out the thumbnail (or placeholder).
     *
     * @param CategoriesController Object $sender
     * @param CategoriesController Array $args
     */
    public function categoriesController_beforeDiscussionContent_handler($sender, $args) {
        if (c('Vanilla.Discussions.Layout') === 'table') {
            return;
        }
        echo $this->displayCatalogueImage(val('Discussion', $args));
    }

    /**
     * If the Discussions Layout is table, echo out the thumbnail (or placeholder).
     *
     * @param CategoriesController $sender
     * @param array $args
     */
    public function categoriesController_BeforeDiscussionTitle_handler(CategoriesController $sender, $args) {
        if (c('Vanilla.Discussions.Layout') === 'table') {
            echo $this->displayCatalogueImage(val('Discussion', $args));
        }
    }

    /**
     * Add the CSS class to "catalogue" displayed discussions in the discussion view.
     *
     * @param DiscussionsController $sender
     * @param array $args
     */
    public function discussionsController_beforeDiscussionName_handler(DiscussionsController $sender, $args) {
        if (valr('Discussion.CatalogueDisplay', $args)) {
            $args['CssClass'] .= ' CatalogueRow';
        }
    }

    /**
     * Add the CSS class to "catalogue" displayed discussions in the categories view.
     *
     * @param CategoriesController $sender
     * @param array $args
     */
    public function categoriesController_beforeDiscussionName_handler(CategoriesController $sender, $args) {
        if (valr('Discussion.CatalogueDisplay', $args)) {
            $args['CssClass'] .= ' CatalogueRow';
        }
    }

    /**
     * If the discussion is in the "catalogue" style, get the first thumbnail and display it in the list view.
     *
     * @param array|object $discussion Discussion row.
     * @return null|string A photo tag, or a placeholder div to be displayed in place of a photo.
     */
    public function displayCatalogueImage($discussion) {
        if (!val('CatalogueDisplay', $discussion)) {
            return;
        }
        $photo = '';
        $imgTag = null;
        $cssClassWrapper = [];
        $imgAttributes = [];
        $catalogueImgURL = discussionUrl($discussion);
        if (!c('CatalogueDisplay.Masonry.Enabled')) {
            $cssClassWrapper[] = 'catalogue-image-wrapper';
        }
        if (!c('CatalogueDisplay.Masonry.Enabled')) {
            $imgAttributes['class'] = 'catalogue-image';
        }
        $eventArguments['Discussion'] = $discussion;
        $eventArguments['catalogueImgURL'] = &$catalogueImgURL;
        $eventArguments['cssClassWrapper'] = &$cssClassWrapper;
        $eventArguments['imgAttributes'] = &$imgAttributes;
        $this->eventManager->fire('beforeCatalogueDisplay', $eventArguments);

        // First extract image URL from inside the body.
        $imageUrl = $this->findImageUrl($discussion);
        if ($imageUrl) {
            $imgTag = img($imageUrl, $imgAttributes);
        }

        // If there is no image, look for the Placeholder Image saved in the config.
        $placeHolderUrl = c('CatalogueDisplay.PlaceHolderImage');
        if (!$imgTag && $placeHolderUrl) {
            $imgAttributes['class'] = 'placeholder-image';
            $imgAttributes['alt'] = t('Placeholder');
            $imgTag = img($placeHolderUrl, $imgAttributes);
        }

        // Apply url to  img
        if ($imgTag) {
            $photo = anchor($imgTag, $catalogueImgURL);
        }

        return $this->renderTwig("/plugins/cataloguedisplay/views/catalogueImage.twig", ['photo' => $photo,
            'cssClassWrapper' => $cssClassWrapper]);
    }

    /**
     * Takes a discussion and parses out the first image in the discussion.
     *
     * @param array|object $discussion Discussion to have image found based on its formatting.
     * @return mixed|string URI of the first image in the Discussion.
     */
    public function findImageUrl($discussion) {
        // Get the image URL from cache.
        $cacheKey = 'catalogueDisplay.thumbnailURL.'.val('DiscussionID', $discussion);
        $imageUrl = Gdn::cache()->get($cacheKey);
        if (!$imageUrl || $imageUrl === Gdn_Cache::CACHEOP_FAILURE) {
            // If no image URL is cached, parse it from the DOM.
            $dom = pQuery::parseStr(Gdn_Format::to(val('Body', $discussion), val('Format', $discussion)));
            if ($dom) {
                if ($dom->query('img')) {
                    // Get the image URL, store it to cache.
                    $discussionImages = $dom->query('img');
                    $imageUrl = $discussionImages->attr('src');
                    Gdn::cache()->store($cacheKey, $imageUrl);
                }
            }
        }

        // if not get the image URL using pQuery and cache the results
        return $imageUrl;
    }

    /**
     * Implement masonry script and css
     *
     * @param CategoriesController $sender
     */
    public function categoriesController_render_before(CategoriesController $sender) {
        if (c('Vanilla.Discussions.Layout') === 'table') {
            return;
        }
        if (c('CatalogueDisplay.Masonry.Enabled')) {
            $sender->addJsFile('library/jQuery-Masonry/jquery.masonry.js', 'plugins/Reactions');
            $sender->addJsFile('masonry-categories.js', 'plugins/cataloguedisplay');
            $sender->addCssFile('catalogue-masonry.css', 'plugins/cataloguedisplay');
        }
    }
}

