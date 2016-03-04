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
    'AuthorEmail' => 'patrick.k@vanillaforums.com'
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

    /**
     * After a discussion is saved in post controller, any images that were uploaded
     * with the discussion are then linked to the discussion, at this point we call the
     * insertDiscussionMedia Handler where we can watermark the image if it is in an assigned category.
     *
     * @param $sender
     * @param $args
     */
    function fileUploadPlugin_insertDiscussionMedia_handler($sender, $args) {
        $watermarkCategories = c('Plugins.Watermark.Categories');
        if(in_array($args['CategoryID'], $watermarkCategories)) {
            $media = $args['AllFilesData'];

            // these params are passed to the image and the thumbnail, theoretically, if a client wants we could create param arrays, one for each.
            $watermarkParams = array(
                'filename' => $_SERVER['DOCUMENT_ROOT'] . '/uploads' . c('Plugins.Watermark.WatermarkPath'),
                'position' => c('Plugins.Watermark.Position', array(0, 0, 0, 0)),
                'resize' => c('Plugins.Watermark.Resize', 70)
            );

            // Loop through all the media that were attached to the discussion, if it is an image, watermark it and its thumbnail.
            foreach ($media as $file) {
                $mediaRow = $sender->MediaModel()->GetID($file);
                if(substr($mediaRow->Type, 0, 5) == 'image') {
                    $this->watermark($_SERVER['DOCUMENT_ROOT'] . '/uploads' . $mediaRow->Path, $watermarkParams, $_SERVER['DOCUMENT_ROOT'] . '/uploads' . $mediaRow->Path)
                            ->watermark($_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $mediaRow->ThumbPath, $watermarkParams, $_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $mediaRow->ThumbPath);
                }
            }
        }
    }

    /**
     * Apply a water mark to a source image.
     *
     * @param $sourcefile
     * @param $watermarkfiles
     * @param $destination
     * @param int $quality
     *
    */
     function watermark($sourcefile, $watermarkParams = array(), $destination, $quality=90) {
         // Since this media was already vetted by the upload script we can trust the file endings to get the type.
         if(stringEndsWith($sourcefile, '.png')) {
             $outputtype = 'png';
             $sourcefile_id = imagecreatefrompng($sourcefile);
         } elseif(stringEndsWith($sourcefile, '.gif')) {
             $outputtype = 'gif';
             $sourcefile_id = imagecreatefromgif($sourcefile);
         } else {
             $outputtype = 'jpg';
             $sourcefile_id = imagecreatefromjpeg($sourcefile);
         }

         // Get the source file size
         $sourcefile_width = imageSX($sourcefile_id);
         $sourcefile_height = imageSY($sourcefile_id);

         // Create the watermark image from the path supplied in params
         $watermarkfile_id = imagecreatefrompng($watermarkParams['filename']);
         imageAlphaBlending($watermarkfile_id, false);
         imageSaveAlpha($watermarkfile_id, true);
         $watermarkfile_width=imageSX($watermarkfile_id);
         $watermarkfile_height=imageSY($watermarkfile_id);

         /**
         * Resize the watermark.
         *
         * The resize value is a percentage of the parent image.
         * The watermark is resized maintaining its aspect ratio.
         * If no resize is supplied, do not resize.
         */
         $resize = (isset($watermarkParams['resize'])) ? $watermarkParams['resize'] : 100;
         $ratio = $watermarkfile_width/$watermarkfile_height;
         $watermark_resize_width = $sourcefile_width * ($resize/100);
         $watermark_resize_height = $sourcefile_height * ($resize/100);
         if ($watermark_resize_width/$watermark_resize_height > $ratio) {
             $watermark_resize_width = round($watermark_resize_height*$ratio);
         } else {
             $watermark_resize_height = round($watermark_resize_width/$ratio);
         }

         $im_dest = imagecreatetruecolor ($watermark_resize_width, $watermark_resize_height);
         imagealphablending($im_dest, false);
         imagecopyresized($im_dest, $watermarkfile_id, 0, 0, 0, 0, $watermark_resize_width, $watermark_resize_height, $watermarkfile_width, $watermarkfile_height);
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
         if($watermarkParams['position'][0] && $watermarkParams['position'][1]) { // top left
             $dest_y = $watermarkParams['position'][0];
             $dest_x = $watermarkParams['position'][1];
         } elseif($watermarkParams['position'][1] && $watermarkParams['position'][2]) { // top right
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

         imagecopy($sourcefile_id, $watermarkfile_id, $dest_x, $dest_y, 0, 0, $watermarkfile_width, $watermarkfile_height);
         imagedestroy($watermarkfile_id);

         if ($outputtype == 'gif') {
             imagegif($sourcefile_id, $destination);
         } elseif($outputtype == 'png') {
             imagepng($sourcefile_id, $destination, $quality);
         } else {
             imagejpeg($sourcefile_id, $destination, $quality);
         }

         imagedestroy($sourcefile_id);

         return $this;
    }

}