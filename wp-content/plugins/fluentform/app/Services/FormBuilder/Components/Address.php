<?php

namespace FluentForm\App\Services\FormBuilder\Components;

use FluentForm\Framework\Helpers\ArrayHelper;

class Address extends BaseComponent
{
    /**
     * Wrapper class for address element
     *
     * @var string
     */
    protected $wrapperClass = 'fluent-address';

    /**
     * Compile and echo the html element
     *
     * @param array     $data [element data]
     * @param \stdClass $form [Form Object]
     *
     * @return void
     */
    public function compile($data, $form)
    {
        $elementName = $data['element'];

        $data = apply_filters('fluentform_rendering_field_data_' . $elementName, $data, $form);

        $rootName = $data['attributes']['name'];
        $hasConditions = $this->hasConditions($data) ? 'has-conditions ' : '';
        $data['attributes']['class'] .= ' ff-name-address-wrapper ' . $this->wrapperClass . ' ' . $hasConditions;
        $data['attributes']['class'] = trim($data['attributes']['class']);

        if ('yes' == ArrayHelper::get($data, 'settings.enable_g_autocomplete')) {
            $data['attributes']['class'] .= ' ff_map_autocomplete';
            if ('yes' == ArrayHelper::get($data, 'settings.enable_g_map')) {
                $data['attributes']['data-ff_with_g_map'] = '1';
            }
            $data['attributes']['data-ff_with_auto_locate'] = ArrayHelper::get($data, 'settings.enable_auto_locate', false);
            do_action('fluentform_address_map_autocomplete', $data, $form);
        }

        $atts = $this->buildAttributes(
            ArrayHelper::except($data['attributes'], 'name')
        );

        //re order fields from version 4.3.2
        if ($order = ArrayHelper::get($data, 'settings.field_order')) {
            $order = array_values(array_column($order, 'value'));
            $fields = ArrayHelper::get($data, 'fields');
            $data['fields'] = array_merge(array_flip($order), $fields);
        }
        ob_start();
        echo '<div ' . $atts . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $atts is escaped before being passed in.
        do_action('fluentform_rendering_address_field', $data, $form);
        if ($label = $data['settings']['label']):
            echo "<div class='ff-el-input--label'>";
            echo '<label aria-label='.wp_strip_all_tags("$label").'>' . fluentform_sanitize_html($data['settings']['label']) . '</label>';
            echo '</div>';
        endif;
        echo "<div class='ff-el-input--content'>";

        $visibleFields = array_chunk(array_filter($data['fields'], function ($field) {
            return $field['settings']['visible'];
        }), 2);

        $googleAutoComplete = 'yes' === ArrayHelper::get($data, 'settings.enable_g_autocomplete');
        foreach ($visibleFields as $chunked) {
            echo "<div class='ff-t-container'>";
            foreach ($chunked as $item) {
                if ($item['settings']['visible']) {
                    $itemName = $item['attributes']['name'];
                    $item['attributes']['data-key_name'] = $itemName;
                    $item['attributes']['name'] = $rootName . '[' . $itemName . ']';

                    if ('select_country' === $item['element'] && $googleAutoComplete) {
                        $selectedCountries = (array) ArrayHelper::get($item, 'attributes.value', []);
                        if ('visible_list' === ArrayHelper::get($item, 'settings.country_list.active_list')) {
                            $selectedCountries = array_unique(
                                array_merge(
                                    $selectedCountries,
                                    ArrayHelper::get($item, 'settings.country_list.visible_list', [])
                                )
                            );
                        }
                        $item['attributes']['data-autocomplete_restrictions'] = json_encode(array_filter($selectedCountries));
                    }

                    $item = apply_filters('fluentform_before_render_item', $item, $form);
                    echo "<div class='ff-t-cell'>";
                    do_action('fluentform_render_item_' . $item['element'], $item, $form);
                    echo '</div>';
                }
            }
            echo '</div>';
        }

        echo '</div>';
        echo '</div>';

        $html = ob_get_clean();

        $this->printContent('fluentform_rendering_field_html_' . $elementName, $html, $data, $form);
    }
}
