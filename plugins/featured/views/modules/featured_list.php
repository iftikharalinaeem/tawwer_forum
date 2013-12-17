<?php

$discussions = $this->Data('Discussions')->ResultArray();

if (is_array($discussions)
&& count(array_filter($discussions))) {

   // The first discussion requires more info.
   $first_discussion = array_shift($discussions);
   $UserModel = new UserModel();
   $user = $UserModel->GetID($first_discussion['InsertUserID']);
   $user_url = UserUrl($user);
   $category_url = CategoryUrl($first_discussion['CategoryID']);

   ?>

   <div class="plugin-featured">
      <h2 class="H">
         <?php echo T('Featured Discussions'); ?>
      </h2>

      <div class="featured-first">
         <a class="featured-first-title" href="<?php echo $first_discussion['Url']; ?>">
            <?php echo $first_discussion['Name']; ?>
         </a>

         <div class="featured-first-meta">
            <a class="PhotoWrap" href="<?php echo $user_url; ?>" title="<?php echo $first_discussion['FirstName']; ?>">
               <img class="ProfilePhoto ProfilePhotoMedium" alt="<?php echo $first_discussion['FirstName']; ?>" src="<?php echo $first_discussion['LastPhoto']; ?>">
            </a>

            <div class="featured-first-name-date">
               <a class="featured-first-username" href="<?php echo $user_url; ?>">
                  <?php echo $first_discussion['FirstName']; ?>
               </a>

               <time class="featured-first-post-date" datetime="<?php echo $first_discussion['DateInserted']; ?>">
                  <?php echo Gdn_Format::Date($first_discussion['DateInserted']); ?>
               </time>
            </div>

            <a class="featured-first-cat" href="<?php echo $category_url; ?>">
               in <?php echo $first_discussion['Category']; ?>
            </a>
         </div>

         <div class="featured-first-body">
            <?php echo SliceParagraph(Gdn_Format::PlainText($first_discussion['Body']), 300); ?>
         </div>
      </div>

      <h3 class="H">Other Featured Discussions</h3>
      <ol class="featured-other DataList">

         <?php foreach ($discussions as $discussion): ?>

            <li>
               <a class="featured-title" href="<?php echo $discussion['Url']; ?>">
                  <?php echo $discussion['Name']; ?>
               </a>
            </li>

         <?php endforeach; ?>

      </ol>
   </div>

   <?php
}