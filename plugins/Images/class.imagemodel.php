<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/
/**
 * Poll Model
 *
 * @package Vanilla
 */
 
/**
 * Manages poll discussions.
 */
class ImageModel extends Gdn_Model {
   
   /**
    * Class constructor. Defines the related database table name.
    * 
    * @since 2.0.0
    * @access public
    */
   public function __construct() {
      parent::__construct('Image');
   }
   
   protected $_CommentModel = NULL;
   
   public function CommentModel() {
      if ($this->_CommentModel === NULL)
         $this->_CommentModel = new CommentModel();
      
      return $this->_CommentModel;
   }
   
   /**
    * Inserts new image(s) and returns the discussion id. If no DiscussionID is
    * present in $formPostValues, it will create a new discussion, using the 
    * first image as the content. Subsequent images will be treated as comments.
    * If DiscussionID is present, it will just create a new comment within that
    * discussion for each image.
    * 
    * @var array $formPostValues The values posted by the form for saving.
    * @var array $commentIDs Array of comment id's created by the save (available by reference).
    */
   public function Save($formPostValues, $settings = FALSE) {
      // Loop through all of the incoming values and validate them      
      $formPostValues = $this->FilterForm($formPostValues);
      $formPostValues['Type'] = 'image'; // Force the "image" discussion type.
      
      
      $discussionID = GetValue('DiscussionID', $formPostValues);
      $image = GetValue('Image', $formPostValues);
      $thumbnail = GetValue('Thumbnail', $formPostValues);
      $caption = GetValue('Caption', $formPostValues);
      $size = GetValue('Size', $formPostValues);
      $images = [];
      foreach ($image as $key => $val) {
         $capt = trim($caption[$key]);
         $images[] = [
             'Image' => $val, 
             'Thumbnail' => $thumbnail[$key], 
             'Caption' => $capt, 
             'Size' => $size[$key]
         ];
      }
      
      if (count($images) == 0)
         $this->Validation->AddValidationResult('Image', 'You must provide at least one image.');
      
      if (count($this->Validation->Results()) > 0)
         return 0;
      
      // We need to space the post time of the comments out so the caching won't break.
      $timestamp = time();

      if (!$discussionID) {
         $image = array_shift($images);
         $serializedImage = dbencode($image);
         // Build the discussion data to be saved
         $discussionFormValues = [
             'Type' => 'Image',
             'Format' => 'Image',
             'CategoryID' => GetValue('CategoryID', $formPostValues),
             'Name' => GetValue('Name', $formPostValues),
             'Body' => Gdn_Format::Image($serializedImage),
             'Attributes' => $serializedImage
         ];

         // Save the discussion
         $discussionModel = new DiscussionModel();
         $discussionID = $discussionModel->Save($discussionFormValues);
         $validationResults = $discussionModel->Validation->Results();
         $this->Validation->AddValidationResult($validationResults);
         if (count($this->Validation->Results()) > 0)
            return 0;
      }

      // Build & save the comments (if there is more than one image being uploaded)
      $commentIDs = [];
      for($i = 0; $i < count($images); $i++) {
         $image = $images[$i];
         $image['DiscussionID'] = $discussionID;
         $commentID = $this->SaveComment($image);
         $commentIDs[] = $commentID;
      }
      $this->CommentIDs = $commentIDs;
         
      // Return the discussion id
      return $discussionID;
   }
   
   public function SaveComment($image, &$timestamp = NULL) {
      $commentModel = $this->CommentModel();
      if ($timestamp === NULL)
         $timestamp = time();
      
      $s = dbencode($image);
      $row = [
            'Type' => 'Image',
            'Format' => 'Image',
            'DiscussionID' => $image['DiscussionID'],
            'Body' => Gdn_Format::Image($image),
            'DateInserted' => Gdn_Format::ToDateTime($timestamp++),
            'Attributes' => $s
        ];
      
      $commentID = $commentModel->Save($row);
      $validationResults = $commentModel->Validation->Results();
      $this->Validation->AddValidationResult($validationResults);
      
      return $commentID;
   }
}
