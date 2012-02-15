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
					<?php comments_popup_link('No Comments &#187;', '1 Comment &#187;', '% Comments &#187;'); ?>					
				</div>
			</div>
			<?php the_content('Continued...'); ?>
		</div>
		
		<div id="respond">
      <?php comments_template(); ?>
		</div>
		<?php endwhile; ?>
	<?php else : ?>
		<h2 class="center">Not Found</h2>
		<p class="center">Sorry, but you are looking for something that isn't here.</p>
	<?php endif; ?>
	</div>
	
<?php get_sidebar(); ?>

<?php get_footer(); ?>