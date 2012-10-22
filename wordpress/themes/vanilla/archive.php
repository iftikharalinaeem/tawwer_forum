<?php get_header(); ?>

	<div id="content">
	<?php if (have_posts()) : ?>



		<?php while (have_posts()) : the_post(); ?>
		
      		
		<div class="post">
		
			<h1>
			<?php $link_values = get_post_custom_values("link"); if ($link_values[0] != NULL) { ?>
			<span class="buzzSource"><?php echo $source_values[0]; ?></span> <?php } else {?>

			<a href="<?php the_permalink() ?>"><?php the_title(); ?></a> <?php } ?>
		</h1>
			
			<?php $source_values = get_post_custom_values("source"); if ($source_values[0] != NULL) { ?>
			<h3><?php echo $source_values[0]; ?>"></h3> <?php } else {?>

			<small>

			<?php the_time('j. M, Y') ?> <b>by</b> <?php the_author_posts_link(); ?> <b>in</b> <?php the_category(', ') ?> <?php the_tags(' | <b>Tags:</b> ', ', ', ''); ?> <?php if ( $user_ID ) : 
			?> | <b>Modify:</b> <?php edit_post_link(); ?> <?php endif; ?>| <?php comments_popup_link('No Comments &#187;', '1 Comment &#187;', '% Comments &#187;'); ?>
			
			</small>
			
			<?php } ?>
			<?php the_excerpt('Read the rest of this entry &raquo;'); ?>
		</div>
		
		
		
		<?php comments_template(); ?>
		
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
	
<?php get_sidebar(); ?>

<?php get_footer(); ?>