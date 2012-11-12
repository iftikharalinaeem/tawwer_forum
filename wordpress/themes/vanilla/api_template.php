<?php
/**
 * Template Name: API Template
 * 
 * Custom API template that displays the API TOC alongside post contents.
 */
?>

<?php get_header(); ?>

<div class="api_toc">
   <div class="api_section">
      <div class="section_heading">Overview</div>
      <div class="section_contents">
         <div class="item"><a href="/blog/api#configuration">Configuration</a></div>
         <div class="item"><a href="/blog/api#usage">Usage</a></div>
         <div class="item"><a href="/blog/api#responsecodes">Response Codes</a></div>
         <div class="item"><a href="/blog/api-smart-id">Smart ID</a></div>
      </div>
   </div>
   
   <div class="api_section">
      <div class="section_heading">Forum Administration</div>
      <div class="section_contents">
         <div class="item"><a href="/blog/api-configuration">/configuration</a></div>
      </div>
   </div>

   <div class="api_section">
      <div class="section_heading">Categories</div>
      <div class="section_contents">
         <div class="item"><a href="/blog/api-categories-add">/categories/add</a></div>
         <div class="item"><a href="/blog/api-categories-edit">/categories/edit</a></div>
         <div class="item"><a href="/blog/api-categories-delete">/categories/delete</a></div>
         <!-- <div class="item"><a href="/blog/api-categories-show">/categories/show</a></div> -->
         <div class="item"><a href="/blog/api-categories-list">/categories/list</a></div>
      </div>
   </div>

   <div class="api_section">
      <div class="section_heading">Discussions</div>
      <div class="section_contents">
         <div class="item"><a href="/blog/api-discussions-add">/discussions/add</a></div>
         <div class="item"><a href="/blog/api-discussions-edit">/discussions/edit</a></div>
         <!-- <div class="item"><a href="/blog/api-discussions-delete">/discussions/delete</a></div> -->
         <!-- <div class="item"><a href="/blog/api-discussions-show">/discussions/show</a></div> -->
         <div class="item"><a href="/blog/api-discussions-list">/discussions/list</a></div>
         <div class="item"><a href="/blog/api-discussions-list-category">/discussions/category</a></div>
      </div>
   </div>

   <div class="api_section">
      <div class="section_heading">Comments</div>
      <div class="section_contents">
         <div class="item"><a href="/blog/api-comments-add">/comments/add</a></div>
         <div class="item"><a href="/blog/api-comments-edit">/comments/edit</a></div>
         <!-- <div class="item"><a href="/blog/api-comments-delete">/comments/delete</a></div> -->
         <!-- <div class="item"><a href="/blog/api-comments-show">/comments/show</a></div> -->
      </div>
   </div>

   <div class="api_section">
      <div class="section_heading">Badges</div>
      <div class="section_contents">
         <div class="item"><a href="/blog/api-badges-give">/badges/give</a></div>
         <div class="item"><a href="/blog/api-badges-user">/badges/user</a></div>
         <div class="item"><a href="/blog/api-badges-list">/badges/list</a></div>
      </div>
   </div>
   
   <div class="api_section">
      <div class="section_heading">Users</div>
      <div class="section_contents">
         <div class="item"><a href="/blog/api-users-edit">/users/edit</a></div>
         <div class="item"><a href="/blog/api-users-multi">/users/multi</a></div>
         <div class="item"><a href="/blog/api-users-notifications">/users/notifications</a></div>
         <div class="item"><a href="/blog/api-users-show">/users/get</a></div>
      </div>
   </div>
   
   <div class="api_section">
      <div class="section_heading">Roles</div>
      <div class="section_contents">
         <div class="item"><a href="/blog/api-roles-list">/roles/list</a></div>
         <div class="item"><a href="/blog/api-roles-get">/roles/get</a></div>
      </div>
   </div>
   
   <div class="api_section">
      <div class="section_heading">Ranks</div>
      <div class="section_contents">
         <div class="item"><a href="/blog/api-ranks-list">/ranks/list</a></div>
         <div class="item"><a href="/blog/api-ranks-get">/ranks/get</a></div>
      </div>
   </div>
   
   <div class="api_section">
      <div class="section_heading">Ignore</div>
      <div class="section_contents">
         <div class="item"><a href="/blog/api-ignore-list">/ignore/list</a></div>
         <div class="item"><a href="/blog/api-ignore-add">/ignore/add</a></div>
         <div class="item"><a href="/blog/api-ignore-remove">/ignore/remove</a></div>
         <div class="item"><a href="/blog/api-ignore-restrict">/ignore/restrict</a></div>
      </div>
   </div>
   
   <div class="api_section">
      <div class="section_heading">Online</div>
      <div class="section_contents">
         <div class="item"><a href="/blog/api-online-privacy">/online/privacy</a></div>
      </div>
   </div>
</div>

<div id="content" class="api">
   <?php if (have_posts()) : ?>

      <?php while (have_posts()) : the_post(); ?>

         <div class="post api">

            <h1><a href="<?php the_permalink() ?>"><?php the_title(); ?></a></h1>

            <?php the_content('Read the rest of this entry &raquo;'); ?>
            <hr/>
         </div>

      <?php endwhile; ?>

      <div class="navigation">
         <div class="alignleft"><?php next_posts_link('&laquo; Older Entries') ?></div>
         <div class="alignright"><?php previous_posts_link('Newer Entries &raquo;') ?></div>
      </div>

   <?php else : ?>

      <h2 class="center">Not Found</h2>
      <p class="center">Sorry, but you are looking for something that isn't here.</p>

   <?php endif; ?>

</div>

<?php get_footer(); ?>