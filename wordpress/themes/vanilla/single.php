<?php get_header(); ?>

	<div id="content">
	<?php if (have_posts()) : ?>

		<?php while (have_posts()) : the_post(); ?>
		
		<div class="post">
			<h1><a href="<?php the_permalink() ?>"><?php the_title(); ?></a></h1>
			<div class="post-meta">
				<div class="post-author"><?php the_author_posts_link(); ?></div>
				<div class="post-date"><?php the_time('M j, Y') ?></div>
				<div class="post-category"><?php the_category(', ') ?></div>
				<?php if ( $user_ID ) : ?><div class="post-options"><?php edit_post_link('Edit'); ?></div><?php endif; ?>
				<div class="post-comments">
				   <?php if (strtotime($post->post_date) < strtotime('10 October 2011')) : ?>
					<?php comments_popup_link('No Comments &#187;', '1 Comment &#187;', '% Comments &#187;'); ?>					
				   <?php else : ?>
					<a href="<?php the_permalink(); ?>#vanilla_comments" vanilla-identifier="<?php echo $post->ID; ?>">Comments</a>
					<?php endif; # Date check ?>
				</div>
			</div>
			<?php the_content('Continued...'); ?>
		</div>
		
   <?php if (strtotime($post->post_date) < strtotime('10 October 2011')) : ?>
   
		<div id="respond">
      <?php comments_template(); ?>
		</div>
		
   <?php else : ?>
   
		<div id="vanilla-comments"></div>
<script type="text/javascript">
// Configuration Settings: Edit before pasting into your web page
var vanilla_forum_url = 'http://forumaboutforums.com/'; // Required: the full http url & path to your vanilla forum
var vanilla_identifier = '<?php echo $post->ID; ?>'; // Required: a unique identifier for the web page & comments
var vanilla_category_id = 3; // vanilla category id to force the discussion to be inserted into?
// var vanilla_url = 'http://localhost/wordpress/building-an-online-commuity/building-an-online-community-around-your-content/';

// Optional
// var vanilla_url = 'http://yourdomain.com/page-with-comments.html'; // Not required: the full http url & path of the page where this script is embedded.
// var vanilla_type = 'blog'; // possibly used to render the discussion body a certain way in the forum? Also used to filter down to foreign types so that matching foreign_id's across type don't clash.
// var vanilla_name = 'Page with Comments';
// var vanilla_body = ''; // Want the forum discussion body to appear a particular way?
// var vanilla_discussion_id = ''; // In case you want to embed a particular discussion
// var vanilla_category_id = ''; // vanilla category id to force the discussion to be inserted into?

// *** DON'T EDIT BELOW THIS LINE ***
(function() {
   var vanilla = document.createElement('script');
   vanilla.type = 'text/javascript';
   var timestamp = new Date().getTime();
   vanilla.src = vanilla_forum_url + '/js/embed.js';
   (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(vanilla);
})();
</script>
<noscript>Please enable JavaScript to view the <a href="http://vanillaforums.com/?ref_noscript">comments powered by Vanilla.</a></noscript>
<div class="vanilla-credit"><a class="vanilla-anchor" href="http://vanillaforums.com">Comments by <span class="vanilla-logo">Vanilla</span></a></div>

		</div>
		
   <?php endif; # Date check ?>
   
		<?php endwhile; ?>

	<?php else : ?>

		<h2 class="center">Not Found</h2>
		<p class="center">Sorry, but you are looking for something that isn't here.</p>

	<?php endif; ?>

	</div>
	
<?php get_sidebar(); ?>

<?php get_footer(); ?>