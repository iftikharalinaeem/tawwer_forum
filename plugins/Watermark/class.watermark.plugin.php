<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 */

$PluginInfo['Watermark'] = array(
    'Name' => 'Watermark Image',
    'Description' => 'Allow for configured categories to watermark the images attached to discussions.',
    'Version' => '1.0.0',
    'RequiredApplications' => array('Vanilla' => '2.2'),
    'RequiredPlugins' => array(
        'FileUpload' => '1.8.4'
    ),
    'MobileFriendly' => true,
    'Author' => "Patrick Kelly",
    'AuthorEmail' => 'patrick.k@vanillaforums.com',
    'SettingsUrl' => '/settings/watermark'
);


/**
 * Class WatermarkPlugin
 *
 * Add custom feature that allows for categories to be manually configured so that the images attached to a discussion
 * will be watermarked. This was done at the request of GolfWorx in conjunction with a feature that will assign categories in which
 * discussions are closed as soon as they are created and they require images to be uploaded with the discussions. The wateremark
 * plugin will presumably be configured to add watermarks to those images.
 */
class WatermarkPlugin extends Gdn_Plugin {

    function __construct() {

    }

    public function base_render_before () {
        gdn::controller()->removeJsFile('autosave.js');
    }
    /**
     * After a discussion is saved in post controller, any images that were uploaded
     * with the discussion are then linked to the discussion, at this point we call the
     * insertDiscussionMedia Handler where we can watermark the image if it is in an assigned category.
     *
     * @param $sender
     * @param $args
     */
    function fileUploadPlugin_insertDiscussionMedia_handler($sender, $args) {
        $watermarkCategories = c('Watermark.WatermarkCategories');
        if (in_array($args['CategoryID'], $watermarkCategories)) {
            $media = $args['AllFilesData'];

            // these params are passed to the image and the thumbnail, theoretically, if a client wants we could create param arrays, one for each.
            $watermarkParams = array(
                'filename' => c('Watermark.WatermarkPath'),
                'position' => c('Watermark.Position', array(0, 0, 0, 0)),
                'resize' => c('Watermark.Resize', 70)
            );

            $quality = c('Watermark.Quality', 70);

            $mediaRow = $sender->MediaModel()->GetID($media[0]);
            if (substr($mediaRow->Type, 0, 5) == 'image') {
//                if (self::watermark($mediaRow->Path, $watermarkParams, $mediaRow->Path, $quality) === true) {
                    if (self::watermark($mediaRow->ThumbPath, $watermarkParams, $mediaRow->ThumbPath, $quality) === true) {
                        return;
                    }
//                }
            }
        }
    }

    /**
     * On the edit category page in the dashboard, insert a checkbox to allow the admin to designate this category
     * as a category that watermarks images on discussion upload.
     *
     * @param $sender
     * @param $args
     */
    public function settingsController_afterCategorySettings_handler($sender, $args) {
        $watermarkImageCategory = c("Watermark.WatermarkCategories");
        if (!$watermarkImageCategory) {
            $watermarkImageCategory = array();
        }
        $isChecked = in_array($sender->data('CategoryID'), $watermarkImageCategory) ? 1 : 0;
        $sender->Form->setValue("Watermark", $isChecked);
        echo "<li>" . $sender->Form->checkBox('Watermark', t("Add a watermark to images uploaded to discussions in this category.")) . "</li>";
    }

    /**
     * Save to the config the categoryIDs of categories that will use watermarking.
     *
     * @param $sender
     * @param $args
     */
    public function categoryModel_beforeSaveCategory_handler ($sender, $args) {
        $watermarkImageCategory = c("Watermark.WatermarkCategories");
        if (!$watermarkImageCategory) {
            $watermarkImageCategory = array();
        }
        $category = $args['FormPostValues'];

        if (!in_array($category['CategoryID'], $watermarkImageCategory) && $category['Watermark']) {
            // Save to the Require Image Category array in config,
            array_push($watermarkImageCategory, $category['CategoryID']);
            saveToConfig("Watermark.WatermarkCategories", $watermarkImageCategory);
        }

        if (in_array($category['CategoryID'], $watermarkImageCategory) && !$category['Watermark']) {
            // Remove from the Require Image Category array in config.
            $key = array_search($category['CategoryID'], $watermarkImageCategory);
            if (false !== $key) {
                unset($watermarkImageCategory[$key]);
            }
            saveToConfig("Watermark.WatermarkCategories", $watermarkImageCategory);
        }
    }

    /**
     * Upload the watermark image.
     *
     * @param $sender
     * @param $args
     */
    public function settingsController_watermark_create($sender, $args) {
        $sender->permission('Garden.Settings.Manage');
        $sender->addSideMenu('dashboard/settings/watermark');
        $sender->title(t('Watermark'));

        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);

        // Set the model on the form.
        $sender->Form->setModel($configurationModel);

        // Get the current logo.
        $watermark = c('Watermark.WatermarkPath');
        if ($watermark) {
            $watermark = ltrim($watermark, '/');
            // Fix the logo path.
            if (stringBeginsWith($watermark, 'uploads/')) {
                $watermark = substr($watermark, strlen('uploads/'));
            }
            $sender->setData('watermark', $watermark);
        }

        // If seeing the form for the first time...
        if (!$sender->Form->authenticatedPostBack()) {
            // Apply the config settings to the form.
            $sender->Form->setData($configurationModel->Data);
        } else {
            if(gdn::request()->post('delete_watermark')) {
                // Remove the existing watermark in form. Redirect back to form.
                $this->removeWatermark();
                return;
            }
            if ($sender->Form->save() !== false) {
                $upload = new Gdn_Upload();
                try {
                    // Validate the upload
                    $tmpImage = $upload->validateUpload('watermark', false);
                    if ($tmpImage) {
                        // Generate the target image name
                        $targetImage = $upload->generateTargetName(PATH_UPLOADS, 'png');
                        $imageBaseName = pathinfo($targetImage, PATHINFO_BASENAME);

                        // Delete any previously uploaded images.
                        if ($watermark) {
                            $upload->delete($watermark);
                        }

                        // Save the uploaded image
                        $parts = $upload->SaveAs(
                            $tmpImage,
                            $imageBaseName
                        );
                        $imageBaseName = $parts['SaveName'];
                        $sender->setData('watermark', $imageBaseName);
                    }
                } catch (Exception $ex) {
                    $sender->Form->addError($ex);
                }
                // If there were no errors, save the path to the logo in the config
                if ($sender->Form->errorCount() == 0) {
                    saveToConfig('Watermark.WatermarkPath', $imageBaseName);
                }
                $sender->informMessage(t("Your settings have been saved."));
            }
        }

        $sender->render('upload', '', 'plugins/Watermark');
    }

    /**
     * Delete the current watermark image. Redirect to remove the watermark embedded on page.
     */
    public function removeWatermark() {
        $watermark = c('Watermark.WatermarkPath', '');
        removeFromConfig('Watermark.WatermarkPath');
        @unlink(PATH_ROOT.DS.$watermark);
        redirect('/settings/watermark');
    }

    /**
     * Apply a water mark to a source image.
     *
     * @param $copiedSourceFile The file that will be watermarked.
     * @param $watermarkParams Array of params that include the path to the watermark image, its size and positioning.
     * @param $destination Where to save the watermarked image.
     * @param int $quality
     *
     * @return true for chaining purposes.
    */
    static function watermark($sourceFile, $watermarkParams = array(), $name = null, $quality = 90) {
        // Since this media was already vetted by the upload script we can trust the file endings to get the type.
        $uploadImage = new Gdn_UploadImage();
        $copiedSourceFile = $uploadImage->copyLocal($name);
        $destination = $copiedSourceFile;
        Logger::event(
            'watermarking_image',
            Logger::INFO,
            '{destination} chosen',
            array('Name' => $name, 'SourceFile' => $sourceFile, 'CopiedSourceFile' => $copiedSourceFile, 'WatermarkParams' => $watermarkParams)
        );

        $sourcefile_id = false;
        if (stringEndsWith($copiedSourceFile, '.png')) {
            $outputtype = 'png';
            $sourcefile_id = imagecreatefrompng($copiedSourceFile);
        } elseif (stringEndsWith($copiedSourceFile, '.gif')) {
            $outputtype = 'gif';
            $sourcefile_id = imagecreatefromgif($copiedSourceFile);
        } else {
            $outputtype = 'jpg';
            $sourcefile_id = imagecreatefromjpeg($copiedSourceFile);
        }

        if($sourcefile_id === false) {
            die('No Source file.');
        }

        Logger::event(
            'watermarking_image',
            Logger::INFO,
            'SourceImage "{sourcefile_id}" made',
            array('Sourcefile ID' => $sourcefile_id, 'SourceFile' => $sourceFile, 'CopiedSourceFile' => $copiedSourceFile, 'WatermarkParams' => $watermarkParams)
        );

        // Get the source file size
        $sourcefile_width = imageSX($sourcefile_id);
        $sourcefile_height = imageSY($sourcefile_id);

        Logger::event(
            'watermarking_image',
            Logger::INFO,
            'SourceImage measured',
            array('\$sourcefile_height ID' => $sourcefile_height, '\$sourcefile_width' => $sourcefile_width)
        );

        // Create the watermark image from the path supplied in params
        $upload = new Gdn_Upload();
        $copiedWatermarkSource = $upload->copyLocal($watermarkParams['filename']);
        $watermarkfile_id = imagecreatefrompng($copiedWatermarkSource);

        if($watermarkfile_id === false) {
            die('No Watermark file was made.');
        }
//        imageAlphaBlending($watermarkfile_id, false);
//        imagesavealpha($watermarkfile_id, true);
        $watermarkfile_width=imageSX($watermarkfile_id);
        $watermarkfile_height=imageSY($watermarkfile_id);

        Logger::event(
            'watermarking_image',
            Logger::INFO,
            'WatermarkImage "{watermarkfile_id}" made',
            array('WaterMarkFile Copied Source' => $copiedWatermarkSource, '\$watermarkfile_height' => $watermarkfile_height, '\$watermarkfile_width' => $watermarkfile_width, 'WatermarkParams' => $watermarkParams)
        );

        /**
        * Resize the watermark.
        *
        * The resize value is a percentage of the parent image.
        * The watermark is resized maintaining its aspect ratio.
        * If no resize is supplied, do not resize.
        */
        $resize = $watermarkParams['resize'];
        $ratio = $watermarkfile_width/$watermarkfile_height;
        $watermark_resize_width = $sourcefile_width * ($resize/100);
        $watermark_resize_height = $sourcefile_height * ($resize/100);
        if ($watermark_resize_width/$watermark_resize_height > $ratio) {
            $watermark_resize_width = round($watermark_resize_height*$ratio);
        } else {
            $watermark_resize_height = round($watermark_resize_width/$ratio);
        }

        $watermark_resize_height = $watermarkfile_height;
        $watermark_resize_width = $watermarkfile_width;
        $im_dest = imagecreatetruecolor ($watermark_resize_width, $watermark_resize_height);
        imagealphablending($im_dest, false);
        if (imagecopyresized($im_dest, $watermarkfile_id, 0, 0, 0, 0, $watermark_resize_width, $watermark_resize_height, $watermarkfile_width, $watermarkfile_height) === false) {
            die('Failed to resize watermark');
        }
        imagesavealpha($im_dest, true);
        imagedestroy($watermarkfile_id);
        $watermarkfile_id = $im_dest;
        $watermarkfile_width = $watermark_resize_width;
        $watermarkfile_height = $watermark_resize_height;

        /**
         * Position the watermark.
         *
         * Positioning is done similar to CSS standard short hand positioning
         * i.e it takes and array of 4 coordinates (top, left, right bottom)
         * If the first 2 coordinates are present, position it from the top left corner.
         * If the second 2 coordinates are present, top right corner.
         * If the the third 2 coordinates are present, bottom right corner.
         * If the first and fourth coordinates are present, bottom left corner,
         * and if no coordinates are present, center it.
         */
        if ($watermarkParams['position'][0] && $watermarkParams['position'][1]) { // top left
            $dest_y = $watermarkParams['position'][0];
            $dest_x = $watermarkParams['position'][1];
        } elseif ($watermarkParams['position'][1] && $watermarkParams['position'][2]) { // top right
            $dest_y = $watermarkParams['position'][1];
            $dest_x = $sourcefile_width - ($watermarkParams['position'][2] + $watermarkfile_width);
        } elseif ($watermarkParams['position'][2] && $watermarkParams['position'][3]) { // bottom right
            $dest_y = $sourcefile_height - ($watermarkParams['position'][2] + $watermarkfile_height);
            $dest_x = $sourcefile_width - ($watermarkParams['position'][3] + $watermarkfile_width);
        } elseif ($watermarkParams['position'][0] && $watermarkParams['position'][3]) { // bottom left
            $dest_y = $sourcefile_height - ($watermarkParams['position'][0] + $watermarkfile_height);
            $dest_x = $watermarkParams['position'][3];
        } else { // center
            $dest_x = ( $sourcefile_width / 2 ) - ( $watermarkfile_width / 2 ); // centered
            $dest_y = ( $sourcefile_height / 2 ) - ( $watermarkfile_height / 2 ); // centered
        }

        if (imagecopy($sourcefile_id, $watermarkfile_id, $dest_x, $dest_y, 0, 0, $watermarkfile_width, $watermarkfile_height) === false) {
            die('Unable to create the cotton pickin\' image.');
        }
        imagedestroy($watermarkfile_id);

        if ($outputtype == 'gif') {
            imagegif ($sourcefile_id, $copiedSourceFile);
        } elseif ($outputtype == 'png') {
            imagepng($sourcefile_id, $copiedSourceFile, $quality);
        } else {
            imagejpeg($sourcefile_id, $copiedSourceFile, $quality);
        }


        Logger::event(
            'watermarking_image',
            Logger::INFO,
            'Before Save',
            array('\$destination ID' => $destination)
        );

        $savedAs = $uploadImage->saveImageAs($copiedSourceFile, $name);

        imagedestroy($sourcefile_id);

        Logger::event(
            'watermarking_image',
            Logger::INFO,
            'Before Return',
            array('$savedAs' => $savedAs)
        );

        return true;
    }
}