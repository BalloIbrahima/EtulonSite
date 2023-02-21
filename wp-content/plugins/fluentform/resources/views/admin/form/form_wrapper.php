<div class="wrap ff_form_wrap ff_screen_<?php echo esc_attr($route); ?>">
	<?php do_action('fluentform_before_form_screen_wrapper', $form_id, $route); ?>
	
	<div class="form_internal_menu">

        <div title="<?php echo esc_html($form->title); ?>" class="ff_form_name" id="js-ff-nav-title">
			<span><?php echo esc_html($form->title); ?></span>
		</div>

		<ul class="ff_setting_menu">
			<?php foreach ($menu_items as $menu_index => $menu_item): ?>
				<li class="<?php if ($route == $menu_item['slug']) echo "active"; ?>">
                    <a href="<?php echo esc_url($menu_item['url']); ?><?php if (isset($menu_item['hash'])) echo "#". esc_attr($menu_item['hash']); ?>">
                        <?php echo esc_html($menu_item['title']); ?>
                    </a>
                </li>
			<?php endforeach; ?>
		</ul>
		
		<div class="ff-navigation-right">
			<?php do_action('fluentform_after_form_navigation', $form_id, $route); ?>
			<?php do_action('fluentform_after_form_navigation_' . $route, $form_id); ?>

			<div id="more-menu" class="pull-right">
				<more-menu />
			</div>
		</div>
	</div>

	<div class="ff_form_application_container">
		<?php do_action('ff_fluentform_form_application_view_' . $route, $form_id); ?>
	</div>
	
	<?php do_action('fluentform_after_form_screen_wrapper', $form_id, $route); ?>
</div>
