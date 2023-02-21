<?php

namespace FluentForm\App\Services\FormBuilder\Components;

use FluentForm\Framework\Helpers\ArrayHelper;

class CustomHtml extends BaseComponent
{
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

        $hasConditions = $this->hasConditions($data) ? 'has-conditions ' : '';
        $cls = trim($this->getDefaultContainerClass() . ' ff-' . $elementName . ' ' . $hasConditions);
        if ($containerClass = ArrayHelper::get($data, 'settings.container_class')) {
            $cls .= ' ' . $containerClass;
        }
        $atts = $this->buildAttributes(
            ArrayHelper::except($data['attributes'], 'name')
        );
        $html = "<div class='" . esc_attr($cls) . "' {$atts}>" . fluentform_sanitize_html($data['settings']['html_codes']) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $atts is escaped before being passed in.

        $this->printContent('fluentform_rendering_field_html_' . $elementName, $html, $data, $form);
    }
}
