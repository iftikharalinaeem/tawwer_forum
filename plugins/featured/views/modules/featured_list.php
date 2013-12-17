<?php

$discussions = $this->Data('Discussions')->ResultArray();

if (is_array($discussions)
&& count(array_filter($discussions))) {

   $first_discussion = array_shift($discussions);

   ?>

   <div class="FeaturedWrap">
      <h2 class="H">
         <?php echo T('Featured Discussions'); ?>
      </h2>

      <div class="featured-first">
         <a class="featured-first-title" href="<?php echo $first_discussion['Url']; ?>">
            <?php echo $first_discussion['Name']; ?>
         </a>

         <div class="featured-first-meta">
            <a class="featured-first-username">
               <?php echo $first_discussion['FirstName']; ?>
            </a>

            <time datetime="<?php echo $first_discussion['DateInserted']; ?>" class="featured-first-post-date">
               <?php echo $first_discussion['DateInserted']; ?>
            </time>

            <a class="featured-first-cat">
               in <?php echo $first_discussion['Tags']; ?>
            </a>

            <div class="featured-first-body">
               <?php echo $first_discussion['Body']; ?>
            </div>
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