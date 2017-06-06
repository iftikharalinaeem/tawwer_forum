<?php if (!defined('APPLICATION')) exit;

/**
 * Category Banners Plugin
 *
 * @author    Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license   Proprietary
 * @since     1.0.0
 */
class CategoryBannersPlugin extends Gdn_Plugin {

    const CATEGORY_CATEGORY_BANNERS_COLUMN_NAME = "BannerImage";

    /**
     * This will run when you "Enable" the plugin.
     *
     * @return void
     */
    public function setup() {
        $this->structure();
    }

    /**
     * Runs structure.php on /utility/update and on enabling the plugin.
     *
     * @return void
     */
    public function structure() {
        Gdn::structure()
            ->table('Category')
            ->column(self::CATEGORY_CATEGORY_BANNERS_COLUMN_NAME, 'varchar(255)', true)
            ->set();
    }

    /**
     * Get the slug of the banner image for a category
     *
     * @param Category $category
     * @return void
     */
    public function getCategoryBannerImageSlug($category) {
        val(self::CATEGORY_CATEGORY_BANNERS_COLUMN_NAME, $category);
    }

    /**
     * Handle the postback for the additional form field
     *
     * @param SettingsController $sender The settings controller
     *
     * @return void
     */
    public function settingsController_addEditCategory_handler($sender) {
        $categoryID = val('CategoryID', $sender->Data);
        if ($sender->Form->authenticatedPostBack()) {
            $upload = new Gdn_Upload();
            $tmpImage = $upload->validateUpload('BannerImage_New', false);
            if ($tmpImage) {
                // Generate the target image name
                $targetImage = $upload->generateTargetName(PATH_UPLOADS);
                $imageBaseName = pathinfo($targetImage, PATHINFO_BASENAME);

                // Save the uploaded image
                $parts = $upload->saveAs(
                    $tmpImage,
                    $imageBaseName
                );
                $sender->Form->setFormValue('BannerImage', $parts['SaveName']);
            }
        }
    }


    /**
     * Add additional image upload input to the category page form.
     *
     * @param VanillaSettingsController $sender The controller for the settings page.
     *
     * @return void
     */
    public function vanillaSettingsController_afterCategorySettings_handler($sender) {
        echo $sender->Form->imageUploadPreview(
            'BannerImage',
            t('Banner Image'),
            t('The banner displayed at the top of each page.'),
            'vanilla/settings/deletecategorybannerimage/'.$sender->Category->CategoryID
        );
    }


    /**
     * Endpoints for deleting the extra category image from the category
     *
     * @param VanillaSettingsController $sender The controller for the settings page.
     *
     * @return void
     */
    public function vanillaSettingsController_deleteCategoryBannerImage_create($sender, $categoryID = '') {
        // Check permission
        $sender->permission(['Garden.Community.Manage', 'Garden.Settings.Manage'], false);

        if ($categoryID && Gdn::request()->isAuthenticatedPostBack(true)) {
            // Do removal, set message
            $categoryModel = CategoryModel::instance();
            $categoryModel->setField($categoryID, self::CATEGORY_CATEGORY_BANNERS_COLUMN_NAME, null);
            $sender->informMessage(t('Category banner image was successfully deleted.'));
        }

        $sender->RedirectUrl = '/vanilla/settings/categories';
        $sender->render('blank', 'utility', 'dashboard');
    }
}
