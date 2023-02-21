		<!-- Page Footer -->
		<footer id="page-footer">

			<div class="footer-copyright">
				
				<div class="credit">
					<?php
					$theme_data	= wp_get_theme();
					/* translators: %1$s: theme name, %2$s link, %3$s theme author */
					printf( __( '%1$s Theme by <a href="%2$s">%3$s.</a>', 'royal-elementor-kit' ), esc_html( $theme_data->Name ), esc_url( 'https://royal-elementor-addons.com/' ), $theme_data->Author );
					?>
				</div>

			</div>
			
		</footer><!-- #page-footer -->

	</div><!-- #page-wrap -->

<?php wp_footer(); ?>

</body>
</html>