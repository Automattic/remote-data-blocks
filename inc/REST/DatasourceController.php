<?php declare(strict_types = 1);

namespace RemoteDataBlocks\REST;

use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;
use RemoteDataBlocks\WpdbStorage\DatasourceCrud;
use WP_REST_Controller;

defined( 'ABSPATH' ) || exit();

class DatasourceController extends WP_REST_Controller {
	public function __construct() {
		$this->namespace = REMOTE_DATA_BLOCKS__REST_NAMESPACE;
		$this->rest_base = 'data-sources';
	}

	public function register_routes(): void {
		// get_items list
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_items' ],
				'permission_callback' => [ $this, 'get_items_permissions_check' ],
			]
		);

		// get_item
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<uuid>[\w-]+)',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_item' ],
				'permission_callback' => [ $this, 'get_item_permissions_check' ],
				'args'                => [
					'uuid' => [
						'type'     => 'string',
						'required' => true,
					],
				],
			]
		);

		// create_item
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'create_item' ],
				'permission_callback' => [ $this, 'create_item_permissions_check' ],
				'args'                => [
					'service' => [
						'type'     => 'string',
						'required' => true,
						'enum'     => REMOTE_DATA_BLOCKS__SERVICES,
					],
				],
			]
		);

		// update_item
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<uuid>[\w-]+)',
			[
				'methods'             => 'PUT',
				'callback'            => [ $this, 'update_item' ],
				'permission_callback' => [ $this, 'update_item_permissions_check' ],
			]
		);

		// delete_item
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<uuid>[\w-]+)',
			[
				'methods'             => 'DELETE',
				'callback'            => [ $this, 'delete_item' ],
				'permission_callback' => [ $this, 'delete_item_permissions_check' ],
			]
		);

		// item_slug_conflicts
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/slug-conflicts',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'item_slug_conflicts' ],
				'permission_callback' => [ $this, 'item_slug_conflicts_permissions_check' ],
			]
		);
	}

	public function create_item( WP_REST_Request $request ): WP_REST_Response {
		$item = DatasourceCrud::register_new_data_source( $request->get_json_params() );
		return rest_ensure_response( $item );
	}

	public function get_items( WP_REST_Request $request ): WP_REST_Response {
		$code_configured_data_sources = ConfigStore::get_datasources_displayable();
		$ui_configured_data_sources   = DatasourceCrud::get_data_sources_list();
		return rest_ensure_response( array_merge( $code_configured_data_sources, $ui_configured_data_sources ) );
	}

	public function get_item( WP_REST_Request $request ): WP_REST_Response {
		$response = DatasourceCrud::get_item_by_uuid( DatasourceCrud::get_data_sources(), $request->get_param( 'uuid' ) );
		return rest_ensure_response( $response );
	}

	public function update_item( WP_REST_Request $request ): WP_REST_Response {
		$item = DatasourceCrud::update_item_by_uuid( $request->get_param( 'uuid' ), $request->get_json_params() );
		return rest_ensure_response( $item );
	}

	public function delete_item( WP_REST_Request $request ): WP_REST_Response {
		$result = DatasourceCrud::delete_item_by_uuid( $request->get_param( 'uuid' ) );
		return rest_ensure_response( $result );
	}

	public function item_slug_conflicts( WP_REST_Request $request ): WP_REST_Response {
		$slug = $request->get_param( 'slug' );
		$uuid = $request->get_param( 'uuid' ) ?? '';
		if ( empty( $slug ) ) {
			return new \WP_Error(
				'missing_slug',
				__( 'Missing slug parameter.', 'remote-data-blocks' ),
				array( 'status' => 400 )
			);
		}
		$validation_status = DatasourceCrud::validate_slug( $slug, $uuid );
		$result            = [
			'exists' => true !== $validation_status,
		];
		return rest_ensure_response( $result );
	}

	// These all require manage_options for now, but we can adjust as needed

	public function get_item_permissions_check( WP_REST_Request $request ): bool {
		return current_user_can( 'manage_options' );
	}

	public function get_items_permissions_check( WP_REST_Request $request ): bool {
		return current_user_can( 'manage_options' );
	}

	public function create_item_permissions_check( WP_REST_Request $request ): bool {
		return current_user_can( 'manage_options' );
	}

	public function update_item_permissions_check( WP_REST_Request $request ): bool {
		return current_user_can( 'manage_options' );
	}

	public function delete_item_permissions_check( WP_REST_Request $request ): bool {
		return current_user_can( 'manage_options' );
	}

	public function item_slug_conflicts_permissions_check( WP_REST_Request $request ): bool {
		return current_user_can( 'manage_options' );
	}
}
