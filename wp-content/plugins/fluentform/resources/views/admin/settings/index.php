<?php

use FluentForm\App\Helpers\Helper;
use FluentForm\Framework\Helpers\ArrayHelper;

?>
<?php do_action('fluentform_global_menu'); ?>

<div class="ff_form_wrap">
    <div class="ff_form_wrap_area">
        <h2><?php _e('Fluent Forms Global Settings', 'fluentform'); ?></h2>
        <div class="ff_settings_wrapper">
            <?php do_action('fluentform_before_global_settings_wrapper'); ?>
            <div class="ff_settings_sidebar">
                <ul class="ff_settings_list">
                    <li>
                        <a data-hash="settings"
                           href="<?php echo esc_url(Helper::makeMenuUrl('fluent_forms_settings', [
                               'hash' => 'settings'
                           ])); ?>">
                            <?php echo __('Settings'); ?>
                        </a>
                    </li>
                    <li class="<?php echo esc_attr(Helper::getHtmlElementClass('managers', $currentComponent)); ?> ff_menu_item_managers">
                        <a data-hash="managers"
                           href="<?php echo esc_url(Helper::makeMenuUrl('fluent_forms_settings', [
                               'hash' => 'managers'
                           ])); ?>">
                            <?php echo __('Managers'); ?>
                        </a>
                    </li>
                    <li class="<?php echo esc_attr(Helper::getHtmlElementClass('double_optin_settings', $currentComponent)); ?> ff_menu_item_double_optin">
                        <a data-hash="double_optin_settings"
                           href="<?php echo esc_url(Helper::makeMenuUrl('fluent_forms_settings', [
                               'hash' => 'double_optin_settings'
                           ])); ?>">
                            <?php echo __('Double Optin Settings', 'fluentform'); ?>
                        </a>
                    </li>
                    <?php foreach ($components as $componentName => $component): ?>
                        <li class="<?php echo esc_attr(Helper::getHtmlElementClass($component['hash'], $currentComponent)); ?> ff_item_<?php echo esc_attr($componentName); ?>">
                            <a data-settings_key="<?php echo esc_attr(ArrayHelper::get($component, 'settings_key')); ?>"
                               data-component="<?php echo esc_attr(ArrayHelper::get($component, 'component', '')); ?>"
                               data-hash="<?php echo esc_attr(ArrayHelper::get($component, 'hash', '')); ?>"
                               href="<?php echo esc_url(Helper::makeMenuUrl('fluent_forms_settings', $component)); ?>"
                            >
                                <?php echo esc_attr($component['title']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="ff_settings_container">
                <?php do_action('fluentform_global_settings_component_' . $currentComponent); ?>
            </div>
            <?php do_action('fluentform_after_global_settings_wrapper'); ?>
        </div>
    </div>
</div>