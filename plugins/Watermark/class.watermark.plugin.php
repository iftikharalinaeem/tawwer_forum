<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

/**
 * Class WatermarkPlugin
 *
 * Add custom feature that allows for categories to be manually configured so that the images attached to a discussion
 * will be watermarked. This was done at the request of GolfWorx in conjunction with a feature that will assign categories in which
 * discussions are closed as soon as they are created and they require images to be uploaded with the discussions. The wateremark
 * plugin will presumably be configured to add watermarks to those images.
 */
class WatermarkPlugin extends Gdn_Plugin {

    /**
     * After a discussion is saved in post controller, any images that were uploaded
     * with the discussion are then linked to the discussion, at this point we call the
     * insertDiscussionMedia Handler where we can watermark the image if it is in an assigned category.
     *
     * @param $sender
     * @param $args
     */
    public function editorPlugin_beforeSaveUploads_handler($sender, $args) {
        $watermarkCategories = c('Watermark.WatermarkCategories');
        if (in_array($args['CategoryID'], $watermarkCategories)) {
            $filePath = $args['TmpFilePath'];
            $fileExtension = $args['FileExtension'];

            // these params are passed to the image and the thumbnail, theoretically, if a client wants we could create param arrays, one for each.
            $watermarkParams = [
                'filename' => c('Watermark.WatermarkPath'),
                'position' => c('Watermark.Position', [0, 0, 0, 0]),
                'resize' => c('Watermark.Resize', 70)
            ];

            $quality = c('Watermark.Quality', 70);

            if (self::watermark($filePath, $watermarkParams, $fileExtension, $quality) === true) {
                return true;
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
    public function vanillaSettingsController_afterCategorySettings_handler($sender, $args) {
        if (!$sender->data('CategoryID') || !c('Watermark.WatermarkPath')) {
            // We can only add watermarking to a category after it has been created, in edit mode, when we have a CategoryID
            // and if there is a Watermark Image to add to image uploads.
            return;
        }
        $watermarkImageCategory = c("Watermark.WatermarkCategories");
        if (!$watermarkImageCategory) {
            $watermarkImageCategory = [];
        }
        $isChecked = in_array($sender->data('CategoryID'), $watermarkImageCategory) ? 1 : 0;
        $sender->Form->setValue("Watermark", $isChecked);
        echo '<li class="form-group">'.$sender->Form->toggle('Watermark', t('Add a watermark to images uploaded to discussions in this category.')).'</li>';
    }

    /**
     * Save to the config the categoryIDs of categories that will use watermarking.
     *
     * @param $sender
     * @param $args
     */
    public function categoryModel_beforeSaveCategory_handler($sender, $args) {
        $watermarkImageCategory = c("Watermark.WatermarkCategories");
        if (!$watermarkImageCategory) {
            $watermarkImageCategory = [];
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
        $validation->addRule('validWatermarkType', 'function:validWaterMarkType');
        $validation->applyRule('watermark_upload', 'validWatermarkType', t('Watermark has to be in the PNG format'));
        $configurationModel = new Gdn_ConfigurationModel($validation);

        // Set the model on the form.
        $sender->Form->setModel($configurationModel);
        // Get the current logo.
        $uploaded_watermark = c('Watermark.WatermarkPath');
        if ($uploaded_watermark) {
            $uploaded_watermark = ltrim($uploaded_watermark, '/');
            // Fix the logo path.
            if (stringBeginsWith($uploaded_watermark, 'uploads/')) {
                $uploaded_watermark = substr($uploaded_watermark, strlen('uploads/'));
            }
            $sender->setData('uploaded_watermark', $uploaded_watermark);
        }

        // If seeing the form for the first time...
        if (!$sender->Form->authenticatedPostBack()) {
            // Apply the config settings to the form.
            $sender->Form->setData($configurationModel->Data);
        } else {
            if (gdn::request()->post('delete_watermark')) {
                // Remove the existing watermark in form. Redirect back to form.
                $removed = false;
                if ($this->removeWatermark()) {
                    $removed = true;
                    $sender->informMessage(t("Your watermark has been deleted."));
                }
            }

            $upload = new Gdn_Upload();
            try {
                // Validate the upload
                $tmpImage = $upload->validateUpload('watermark_upload', false);
                if ($tmpImage) {
                    // Generate the target image name
                    $targetImage = $upload->generateTargetName(PATH_UPLOADS, 'png');
                    $imageBaseName = pathinfo($targetImage, PATHINFO_BASENAME);

                    // Delete any previously uploaded images.
                    if ($uploaded_watermark) {
                        $upload->delete($uploaded_watermark);
                    }

                    // Save the uploaded image
                    $parts = $upload->saveAs(
                        $tmpImage,
                        $imageBaseName
                    );
                    $imageBaseName = $parts['SaveName'];
                    $sender->setData('watermark_upload', $imageBaseName);
                }
            } catch (Exception $ex) {
                $sender->Form->addError($ex);
            }
            // If there were no errors, save the path to the logo in the config
            if ($sender->Form->errorCount() == 0) {
                saveToConfig('Watermark.WatermarkPath', $imageBaseName);
            }
            if (!$removed) {
                $sender->informMessage(t("Your settings have been saved."));
            }
        }
        $sender->render('upload', '', 'plugins/Watermark');
    }

    /**
     * Delete the current watermark image. Redirect to remove the watermark embedded on page.
     * @return bool true on success, false on failure.
     */
    public function removeWatermark() {
        $watermark = c('Watermark.WatermarkPath', '');
        removeFromConfig('Watermark.WatermarkPath');
        $upload = new Gdn_Upload();
        return $upload->delete($watermark);
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
    static function watermark($sourceFile, $watermarkParams = [], $extension = null, $quality = 90) {
        $destination = $sourceFile;
        Logger::event(
            'watermarking_image',
            Logger::INFO,
            'Destination chosen',
            ['SourceFile' => $sourceFile, 'CopiedSourceFile' => $sourceFile, 'WatermarkParams' => $watermarkParams]
        );

        if ($extension === 'png') {
            $outputType = 'png';
            $sourceFileID = imagecreatefrompng($sourceFile);
        } elseif ($extension === 'gif') {
            $outputType = 'gif';
            $sourceFileID = imagecreatefromgif($sourceFile);
        } else {
            $outputType = 'jpg';
            $sourceFileID = imagecreatefromjpeg($sourceFile);
        }

        if ($sourceFileID === false) {
            die('No Source file.');
        }

        Logger::event(
            'watermarking_image',
            Logger::INFO,
            'SourceImage made',
            ['Sourcefile ID' => $sourceFileID, 'SourceFile' => $sourceFile, 'CopiedSourceFile' => $sourceFile, 'WatermarkParams' => $watermarkParams]
        );

        // Get the source file size
        $sourcefileWidth = imagesx($sourceFileID);
        $sourcefileHeight = imagesy($sourceFileID);

        Logger::event(
            'watermarking_image',
            Logger::INFO,
            'SourceImage measured',
            ['\$sourcefile_height ID' => $sourcefileHeight, '\$sourcefile_width' => $sourcefileWidth]
        );

        // Create the watermark image from the path supplied in params
        $upload = new Gdn_Upload();
        $copiedWatermarkSource = $upload->copyLocal($watermarkParams['filename']);
        $watermarkFileID = imagecreatefrompng($copiedWatermarkSource);

        if ($watermarkFileID === false) {
            die('No Watermark file was made.');
        }

        imagealphablending($watermarkFileID, false);
        imagesavealpha($watermarkFileID, true);
        $watermarkFileWidth = imagesx($watermarkFileID);
        $watermarkFileHeight = imagesy($watermarkFileID);

        Logger::event(
            'watermarking_image',
            Logger::INFO,
            'WatermarkImage made',
            ['WaterMarkFile Copied Source' => $copiedWatermarkSource, '\$watermarkfile_height' => $watermarkFileHeight, '\$watermarkfile_width' => $watermarkFileWidth, 'WatermarkParams' => $watermarkParams]
        );

        /**
         * Resize the watermark.
         *
         * The resize value is a percentage of the parent image.
         * The watermark is resized maintaining its aspect ratio.
         * If no resize is supplied, do not resize.
        */
        $resize = $watermarkParams['resize'];
        $ratio = $watermarkFileWidth/$watermarkFileHeight;
        $watermarkResizeWidth = $sourcefileWidth * ($resize/100);
        $watermarkResizeHeight = $sourcefileHeight * ($resize/100);
        if ($watermarkResizeWidth/$watermarkResizeHeight > $ratio) {
            $watermarkResizeWidth = round($watermarkResizeHeight*$ratio);
        } else {
            $watermarkResizeHeight = round($watermarkResizeWidth/$ratio);
        }

        $imageDestination = imagecreatetruecolor($watermarkResizeWidth, $watermarkResizeHeight);
        imagealphablending($imageDestination, false);
        if (imagecopyresampled($imageDestination, $watermarkFileID, 0, 0, 0, 0, $watermarkResizeWidth, $watermarkResizeHeight, $watermarkFileWidth, $watermarkFileHeight) === false) {
            die('Failed to resample watermark');
        }
        imagesavealpha($imageDestination, true);
        imagedestroy($watermarkFileID);
        $watermarkFileID = $imageDestination;
        $watermarkFileWidth = $watermarkResizeWidth;
        $watermarkFileHeight = $watermarkResizeHeight;

        list($watermarkYPos, $watermarkXPos) = self::watermarkPosition($watermarkParams, $sourcefileWidth, $watermarkFileWidth, $sourcefileHeight, $watermarkFileHeight);


        if (imagecopy($sourceFileID, $watermarkFileID, $watermarkXPos, $watermarkYPos, 0, 0, $watermarkFileWidth, $watermarkFileHeight) === false) {
            die('Unable to impose watermark on sourcefile.');
        }
        imagedestroy($watermarkFileID);

        if ($outputType == 'gif') {
            if (imagegif($sourceFileID, $sourceFile) === false) {
                die('Failed to make the composite gif.');
            }
        } elseif ($outputType == 'png') {
            if (imagepng($sourceFileID, $sourceFile, Gdn_UploadImage::PNG_COMPRESSION) === false) {
                die('Failed to make the composite png.');
            }
        } else {
            if (imagejpeg($sourceFileID, $sourceFile, $quality) === false) {
                die('Failed to make the composite jpg.');
            }
        }


        Logger::event(
            'watermarking_image',
            Logger::INFO,
            'Before Save',
            ['\$destination ID' => $destination]
        );

        imagedestroy($sourceFileID);

        return true;
    }


    /**
     * Position the watermark.
     *
     * @param $watermarkParams
     * @param $sourcefileWidth
     * @param $watermarkFileWidth
     * @param $sourcefileHeight
     * @param $watermarkFileHeight
     * @return array
     */
    public static function watermarkPosition($watermarkParams, $sourcefileWidth, $watermarkFileWidth, $sourcefileHeight, $watermarkFileHeight) {
        /**
         * Positioning is done similar to CSS standard short hand positioning
         * i.e it takes and array of 4 coordinates (top, right, bottom, left)
         * If the first 2 coordinates are present, position it from the top left corner.
         * If the second 2 coordinates are present, top right corner.
         * If the the third 2 coordinates are present, bottom right corner.
         * If the first and fourth coordinates are present, bottom left corner,
         * and if no coordinates are present, center it.
         */
        if ($watermarkParams['position'][0] && $watermarkParams['position'][1]) { // top left
            $destinationY = $watermarkParams['position'][0];
            $destinationX = $watermarkParams['position'][1];
            return [$destinationY, $destinationX];
        } elseif ($watermarkParams['position'][1] && $watermarkParams['position'][2]) { // top right
            $destinationY = $watermarkParams['position'][1];
            $destinationX = $sourcefileWidth - ($watermarkParams['position'][2] + $watermarkFileWidth);
            return [$destinationY, $destinationX];
        } elseif ($watermarkParams['position'][2] && $watermarkParams['position'][3]) { // bottom right
            $destinationY = $sourcefileHeight - ($watermarkParams['position'][2] + $watermarkFileHeight);
            $destinationX = $sourcefileWidth - ($watermarkParams['position'][3] + $watermarkFileWidth);
            return [$destinationY, $destinationX];
        } elseif ($watermarkParams['position'][0] && $watermarkParams['position'][3]) { // bottom left
            $destinationY = $sourcefileHeight - ($watermarkParams['position'][0] + $watermarkFileHeight);
            $destinationX = $watermarkParams['position'][3];
            return [$destinationY, $destinationX];
        } else { // center
            $destinationX = ($sourcefileWidth / 2) - ($watermarkFileWidth / 2); // centered
            $destinationY = ($sourcefileHeight / 2) - ($watermarkFileHeight / 2);
            return [$destinationY, $destinationX]; // centered
        }
    }
}

function validWaterMarkType($val, $info) {
    $fileData = Gdn::request()->getValueFrom(Gdn_Request::INPUT_FILES, $info->Name, false);
    return ($fileData['type'] === 'image/png');
}
