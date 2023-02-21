<?php get_header(); ?>

<!-- Main Container -->
<div class="main-container">

	<article id="post-<?php the_ID(); ?>" <?php post_class('re-theme-post'); ?>>

	<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>	



		<div class="post-media">
			<?php the_post_thumbnail(); ?>
		</div>

		<header class="post-header">

			<h1 class="post-title"><?php the_title(); ?></h1>

			<?php echo '<div class="post-categories">' . get_the_category_list( ',&nbsp;&nbsp;' ) . ' </div>'; ?>

			<div class="post-meta">

				<span class="post-date"><?php the_time( get_option( 'date_format' ) ); ?></span>
				
				<span class="meta-sep">/</span>
				
				<?php comments_popup_link( esc_html__( '0 Comments', 'royal-elementor-kit' ), esc_html__( '1 Comment', 'royal-elementor-kit' ), '% '. esc_html__( 'Comments', 'royal-elementor-kit' ), 'post-comments'); ?>

			</div>

		</header>

		<div class="post-content">

			<?php

			// The Post Content
			the_content('');

			// Post Pagination
			$defaults = array(
				'before' => '<p class="single-pagination">'. esc_html__( 'Pages:', 'royal-elementor-kit' ),
				'after' => '</p>'
			);

			wp_link_pages( $defaults );

			?>
		</div>

		<footer class="post-footer">

			<?php 

			// The Tags
			$tag_list = get_the_tag_list( '<div class="post-tags">','','</div>');
			
			if ( $tag_list ) {
				echo ''. $tag_list;
			}

			?>

			<span class="post-author"><?php esc_html_e( 'By', 'royal-elementor-kit' ); ?>&nbsp;<?php the_author_posts_link(); ?></span>
			
		</footer>

	</article>

	<?php

		endwhile; // Loop End
	endif; // have_posts()

	// If comments are open or we have at least one comment, load up the comment template.
	if ( comments_open() || get_comments_number() ) {

		echo '<div class="comments-area" id="comments">';
			comments_template( '', true );
		echo '</div>';

	}

	?>

</div><!-- .main-container -->

<?php get_footer(); ?>