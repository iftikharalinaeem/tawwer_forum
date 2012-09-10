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
   
   /**
    * Inserts new image(s) and returns the discussion id. If no DiscussionID is
    * present in $FormPostValues, it will create a new discussion, using the 
    * first image as the content. Subsequent images will be treated as comments.
    * If DiscussionID is present, it will just create a new comment within that
    * discussion for each image.
    */
   public function Save($FormPostValues) {
      // Loop through all of the incoming values and validate them      
      $FormPostValues = $this->FilterForm($FormPostValues);
      $FormPostValues['Type'] = 'image'; // Force the "image" discussion type.
      
      $DiscussionID = GetValue('DiscussionID', $FormPostValues);
      $Image = GetValue('Image', $FormPostValues);
      $Thumbnail = GetValue('Thumbnail', $FormPostValues);
      $Caption = GetValue('Caption', $FormPostValues);
      if (count($Image) != count($Caption))
         $this->Validation->AddValidationResult('Image', 'You must provide a caption for each image.');
      
      $Images = array();
      foreach ($Image as $Key => $Val) {
         $Capt = trim($Caption[$Key]);
         $Images[] = array('Image' => $Val, 'Thumbnail' => $Thumbnail[$Key], 'Caption' => $Capt);
         if ($Capt == '')
            $this->Validation->AddValidationResult('Caption', 'You must provide a caption for each image.');
      }
      
      if (count($Images) == 0)
         $this->Validation->AddValidationResult('Image', 'You must provide at least one image.');
      
      if (count($this->Validation->Results()) > 0)
         return 0;

      if (!$DiscussionID) {
         $Image = array_shift($Images);
         
         // Build the discussion data to be saved
         $DiscussionFormValues = array(
             'Type' => 'Image',
             'Format' => 'Image',
             'CategoryID' => GetValue('CategoryID', $FormPostValues),
             'Name' => $Image['Caption'],
             'Body' => serialize($Image)
         );

         // Save the discussion
         $DiscussionModel = new DiscussionModel();
         $DiscussionID = $DiscussionModel->Save($DiscussionFormValues);
         $ValidationResults = $DiscussionModel->Validation->Results();
         $this->Validation->AddValidationResult($ValidationResults);
         if (count($this->Validation->Results()) > 0)
            return 0;
      }

      // Build & save the comments (if there is more than one image being uploaded)
      if (count($Images) > 0) {
         $CommentModel = new CommentModel();
         $CommentFormValues = array(
             'Type' => 'Image',
             'Format' => 'Image',
             'DiscussionID' => $DiscussionID
         );
         for($i = 0; $i < count($Images); $i++) {
            $CommentFormValues['Body'] = serialize($Images[$i]);
            $CommentModel->Save($CommentFormValues);
            $ValidationResults = $CommentModel->Validation->Results();
            $this->Validation->AddValidationResult($ValidationResults);
         }
      }
      
      // Return the discussion id
      return $DiscussionID;
   }   
}
