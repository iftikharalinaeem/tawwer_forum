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
         </div>
      </div>
      
      <div class="api_section">
         <div class="section_heading">Categories</div>
         <div class="section_contents">
            <div class="item"><a href="/blog/api-categories-add">/vanilla/settings/addcategory</a></div>
            <div class="item"><a href="/blog/api-categories-edit">/vanilla/settings/editcategory</a></div>
            <div class="item"><a href="/blog/api-categories-list">/categories/all</a></div>
         </div>
      </div>
      
      <div class="api_section">
         <div class="section_heading">Discussions</div>
         <div class="section_contents">
            <div class="item"><a href="/blog/api-discussions-add">/post/discussion</a></div>
            <div class="item"><a href="/blog/api-discussions-list">/discussions</a></div>
            <div class="item"><a href="/blog/api-discussions-list-category">/categories</a></div>
         </div>
      </div>
      
      <div class="api_section">
         <div class="section_heading">Comments</div>
         <div class="section_contents">
            <div class="item"><a href="/blog/api-comments-add">/post/comment</a></div>
         </div>
      </div>
      
      <div class="api_section">
         <div class="section_heading">Profile</div>
         <div class="section_contents">
            <div class="item"><a href="/blog/api-profile-show">/profile</a></div>
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