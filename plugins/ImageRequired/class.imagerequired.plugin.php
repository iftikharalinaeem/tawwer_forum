<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 */

$PluginInfo['ImageRequired'] = array(
    'Name' => 'Discussion Image Required',
    'Description' => 'Force users to upload images when creating discussions on selected categories.',
    'Version' => '1.0.0',
    'RequiredApplications' => array('Vanilla' => '2.2'),
    'RequiredPlugins' => array(
        'FileUpload' => '1.8.4'
    ),
    'MobileFriendly' => true,
    'Author' => "Patrick Kelly",
    'AuthorEmail' => 'patrick.k@vanillaforums.com'
);


/**
 * Class ImageRequiredplugin
 *
 * Add custom features used by ImageRequired
 */
class ImageRequiredPlugin extends Gdn_Plugin {

    /**
     * Add javascript to:
     *  - Populate hidden form field when an image has been selected for validation purposes.
     *  - When a category has been selected using the dropdown, refresh the page so that the image upload like shows.
     *
     * @param $sender
     * @param $args
     */
    public function base_render_before($sender, $args) {
        $imageRequiredCategory = c("ImageRequired.ImageRequiredCategory");
        if (!$imageRequiredCategory) {
            return;
        }
        $sender->addJsFile('image-required.js', 'plugins/ImageRequired');
        $sender->addDefinition('ImageRequiredCategory', $imageRequiredCategory);
    }


    /**
     * If discussion is in a "Require Image Category", add javascript and hidden fields used for validation of image upload.
     * 
     * @param $sender
     * @param $args
     */
    public function postController_beforeDiscussionRender_handler($sender, $args) {
        $imageRequiredCategory = c("ImageRequired.ImageRequiredCategory");
        if (!$imageRequiredCategory) {
            return;
        }
        $sender->Form->addHidden("imageName", '');
        $sender->Form->addHidden("imageRequiredCategoryChosen", in_array(gdn::request()->get('CategoryID'), $imageRequiredCategory));
    }

    /**
     * On creation of a new discussion, validate that there was an image present.
     *
     * @param $sender
     * @param $args
     */
    public function discussionModel_beforeSaveDiscussion_handler($sender, $args) {
        $imageRequiredCategory = c("ImageRequired.ImageRequiredCategory");
        if (!$imageRequiredCategory) {
            return;
        }

        if (in_array($args['FormPostValues']['CategoryID'], $imageRequiredCategory)) {
            if (class_exists('FileUploadPlugin')) {
                $sender->Validation->applyRule('imageName', 'Required', t('Please upload a photo.'));
            }
        }
    }

    /**
     * On the edit category page in the dashboard, insert a checkbox to allow the admin to designate this category as a "Require Image Category".
     *
     * @param $sender
     * @param $args
     */
    public function settingsController_afterCategorySettings_handler($sender, $args) {
        $imageRequiredCategory = c("ImageRequired.ImageRequiredCategory");
        if (!$imageRequiredCategory) {
            $imageRequiredCategory = array();
        }
        $isChecked = in_array($sender->data('CategoryID'), $imageRequiredCategory) ? 1 : 0;
        $sender->Form->setValue("RequireImage", $isChecked);
        echo "<li>" . $sender->Form->checkBox('RequireImage', t("Make adding an image required when creating a discussion in this category.")) . "</li>";
    }

    /**
     * Save to the config the categoryID that should be "PostOnly"
     *
     * @param $sender
     * @param $args
     */
    public function categoryModel_beforeSaveCategory_handler ($sender, $args) {
        $imageRequiredCategory = c("ImageRequired.ImageRequiredCategory");
        if (!$imageRequiredCategory) {
            $imageRequiredCategory = array();
        }
        $category = $args['FormPostValues'];

        if (!in_array($category['CategoryID'], $imageRequiredCategory) && $category['RequireImage']) {
            // Save to the Require Image Category array in config,
            array_push($imageRequiredCategory, $category['CategoryID']);
            saveToConfig("ImageRequired.ImageRequiredCategory", $imageRequiredCategory);
        }

        if (in_array($category['CategoryID'], $imageRequiredCategory) && !$category['RequireImage']) {
            // Remove from the Require Image Category array in config.
            $key = array_search($category['CategoryID'], $imageRequiredCategory);
            if (false !== $key) {
                unset($imageRequiredCategory[$key]);
            }
            saveToConfig("ImageRequired.ImageRequiredCategory", $imageRequiredCategory);
        }
    }
}