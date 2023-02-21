<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<link rel="profile" href="https://gmpg.org/xfn/11">

<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
	<?php
	if ( function_exists( 'wp_body_open' ) ) {
	    wp_body_open();
	} else {
	    do_action( 'wp_body_open' );
	}
	?>


	<!-- Page Wrapper -->
	<div id="page-wrap">

	<a class="skip-link screen-reader-text" href="#skip-link-target"><?php _e( 'Skip to content', 'royal-elementor-kit' ); ?></a>

	<header id="site-header" class="site-header" role="banner">

		<div class="site-logo">
			<?php

			if ( has_custom_logo() ) :
				the_custom_logo();
			
			elseif ( get_bloginfo( 'name' ) ) :

			?>

				<h1 class="site-title">
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php esc_attr_e( 'Home', 'royal-elementor-kit' ); ?>" rel="home">
						<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
					</a>
				</h1>

				<?php if ( get_bloginfo( 'description', 'display' ) ) : ?>
				<p class="site-description">
					<?php echo esc_html( get_bloginfo( 'description', 'display' ) ); ?>
				</p>
				<?php endif; ?>

			<?php endif; ?>
		</div>

		<?php if ( has_nav_menu('main') ) : ?>
		<nav class="main-navigation" role="navigation">
			<?php wp_nav_menu( [ 'theme_location' => 'main' ] ); ?>
		</nav>
		<?php endif; ?>

	</header>