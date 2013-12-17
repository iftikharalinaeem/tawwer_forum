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
            <?php echo htmlspecialchars($first_discussion['Name']); ?>
         </a>

         <div class="featured-first-meta">

            <?php
               echo UserPhoto($user);
            ?>

            <div class="featured-first-name-date">

               <?php
                  echo UserAnchor($user, 'featured-first-username');
                  echo Gdn_Format::Date($first_discussion['DateInserted'], 'html');
               ?>

            </div>

            <a class="featured-first-cat" href="<?php echo $category_url; ?>">
               in <?php echo $first_discussion['Category']; ?>
            </a>
         </div>

         <div class="featured-first-body">
            <?php echo SliceParagraph(Gdn_Format::PlainText($first_discussion['Body']), 300); ?>
         </div>
      </div>

      <?php if (count($discussions)): ?>

         <h3 class="H"><?php echo T('More Featured Discussions'); ?></h3>

      <?php endif; ?>

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