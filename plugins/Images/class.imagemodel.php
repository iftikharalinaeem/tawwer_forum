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
    * present in $FormPostValues, it will create a new discussion, using the 
    * first image as the content. Subsequent images will be treated as comments.
    * If DiscussionID is present, it will just create a new comment within that
    * discussion for each image.
    * 
    * @var array $FormPostValues The values posted by the form for saving.
    * @var array $CommentIDs Array of comment id's created by the save (available by reference).
    */
   public function Save($FormPostValues, $Settings = FALSE) {
      // Loop through all of the incoming values and validate them      
      $FormPostValues = $this->FilterForm($FormPostValues);
      $FormPostValues['Type'] = 'image'; // Force the "image" discussion type.
      
      
      $DiscussionID = GetValue('DiscussionID', $FormPostValues);
      $Image = GetValue('Image', $FormPostValues);
      $Thumbnail = GetValue('Thumbnail', $FormPostValues);
      $Caption = GetValue('Caption', $FormPostValues);
      $Size = GetValue('Size', $FormPostValues);
      $Images = [];
      foreach ($Image as $Key => $Val) {
         $Capt = trim($Caption[$Key]);
         $Images[] = [
             'Image' => $Val, 
             'Thumbnail' => $Thumbnail[$Key], 
             'Caption' => $Capt, 
             'Size' => $Size[$Key]
         ];
      }
      
      if (count($Images) == 0)
         $this->Validation->AddValidationResult('Image', 'You must provide at least one image.');
      
      if (count($this->Validation->Results()) > 0)
         return 0;
      
      // We need to space the post time of the comments out so the caching won't break.
      $Timestamp = time();

      if (!$DiscussionID) {
         $Image = array_shift($Images);
         $SerializedImage = dbencode($Image);
         // Build the discussion data to be saved
         $DiscussionFormValues = [
             'Type' => 'Image',
             'Format' => 'Image',
             'CategoryID' => GetValue('CategoryID', $FormPostValues),
             'Name' => GetValue('Name', $FormPostValues),
             'Body' => Gdn_Format::Image($SerializedImage),
             'Attributes' => $SerializedImage
         ];

         // Save the discussion
         $DiscussionModel = new DiscussionModel();
         $DiscussionID = $DiscussionModel->Save($DiscussionFormValues);
         $ValidationResults = $DiscussionModel->Validation->Results();
         $this->Validation->AddValidationResult($ValidationResults);
         if (count($this->Validation->Results()) > 0)
            return 0;
      }

      // Build & save the comments (if there is more than one image being uploaded)
      $CommentIDs = [];
      for($i = 0; $i < count($Images); $i++) {
         $Image = $Images[$i];
         $Image['DiscussionID'] = $DiscussionID;
         $CommentID = $this->SaveComment($Image);
         $CommentIDs[] = $CommentID;
      }
      $this->CommentIDs = $CommentIDs;
         
      // Return the discussion id
      return $DiscussionID;
   }
   
   public function SaveComment($Image, &$Timestamp = NULL) {
      $CommentModel = $this->CommentModel();
      if ($Timestamp === NULL)
         $Timestamp = time();
      
      $S = dbencode($Image);
      $Row = [
            'Type' => 'Image',
            'Format' => 'Image',
            'DiscussionID' => $Image['DiscussionID'],
            'Body' => Gdn_Format::Image($Image),
            'DateInserted' => Gdn_Format::ToDateTime($Timestamp++),
            'Attributes' => $S
        ];
      
      $CommentID = $CommentModel->Save($Row);
      $ValidationResults = $CommentModel->Validation->Results();
      $this->Validation->AddValidationResult($ValidationResults);
      
      return $CommentID;
   }
}
