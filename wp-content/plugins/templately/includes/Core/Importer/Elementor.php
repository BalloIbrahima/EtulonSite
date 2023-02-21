<?php

namespace Templately\Core\Importer;

use WP_Error;
use function wp_parse_args;
use function current_user_can;
use Elementor\Core\Settings\Page\Model;

use Elementor\Plugin as ElementorPlugin;
use Elementor\TemplateLibrary\Source_Local as ElementorLocal;

class Elementor extends ElementorLocal {
	/**
	 * Get template data.
	 *
	 * @inheritDoc
	 *
	 * @param array $args Custom template arguments.
	 *
	 * @return array Remote Template data.
	 */
	public function get_data( array $args ) {
		ElementorPlugin::$instance->editor->set_edit_mode( true );

		$args['content'] = $this->replace_elements_ids( $args['content'] );
		$args['content'] = $this->process_export_import_content( $args['content'], 'on_import' );

//		$post_id  = false; // FIXME: need to check later on.
//		$document = ElementorPlugin::$instance->documents->get( $post_id );
//		if ( $document ) {
//			$args['content'] = $document->get_elements_raw_data( $args['content'], true );
//		}
		return $args;
	}

	public function import_in_library( $data ) {
		if ( empty( $data ) ) {
			return new WP_Error( 'file_error', 'Invalid File' );
		}

		$content = $data['content'];

		if ( ! is_array( $content ) ) {
			return new WP_Error( 'file_error', 'Invalid File' );
		}

		$page_settings = $this->page_settings( $data );

		$template_id = $this->save_item( [
			'content' => $content,
			'title' => $data['title'],
			'type' => $data['type'],
			'page_settings' => $page_settings,
		] );

		if ( is_wp_error( $template_id ) ) {
			return $template_id;
		}

		return $this->get_item( $template_id );
	}

	public function create_page( $template_data ){
		$page_settings = $this->page_settings( $template_data );

		$defaults = [
			'post_title'   => isset( $template_data['page_title'] ) ? $template_data['page_title'] : 'Templately: ' . $template_data['title'],
			'page_settings' => $page_settings,
			'status' => current_user_can( 'publish_posts' ) ? 'publish' : 'pending',
		];

		$template_data = wp_parse_args( $template_data, $defaults );

		$document = ElementorPlugin::$instance->documents->create(
			$template_data['type'],
			[
				'post_title' => $template_data['post_title'],
				'post_status' => $template_data['status'],
				'post_type' => 'page',
			]
		);

		if ( is_wp_error( $document ) ) {
			/**
			 * @var WP_Error $document
			 */
			return $document;
		}

		$document->save( [
			'elements' => $template_data['content'],
			'settings' => $page_settings,
		] );

		return $document->get_main_id();
	}

	/**
	 * @param $template_data
	 * @noinspection DuplicatedCode
	 *
	 * @return array
	 */
	private function page_settings( $template_data ) {
		$page_settings = [];

		if ( ! empty( $template_data['page_settings'] ) ) {
			$page = new Model( [
				'id' => 0,
				'settings' => $template_data['page_settings'],
			] );

			$page_settings_data = $this->process_element_export_import_content( $page, 'on_import' );

			if ( ! empty( $page_settings_data['settings'] ) ) {
				$page_settings = $page_settings_data['settings'];
			}
		}

		return $page_settings;
	}

}

