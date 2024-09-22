<?php

namespace RemoteDataBlocks\REST;

use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;
use RemoteDataBlocks\Validation\DatasourceValidator;
use RemoteDataBlocks\WpdbStorage\DatasourceCrud;
use WP_REST_Controller;

defined( 'ABSPATH' ) || exit();

class DatasourceController extends WP_REST_Controller {
	public function __construct() {
		$this->namespace = REMOTE_DATA_BLOCKS__REST_NAMESPACE;
		$this->rest_base = 'data-sources';
	}

	public function register_routes() {
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

	public function create_item( $request ) {
		$item = DatasourceCrud::register_new_data_source( $request->get_json_params() );
		return rest_ensure_response( $item );
	}

	public function get_items( $request ) {
		return rest_ensure_response( ConfigStore::get_displayable_data_sources() );
	}

	public function get_item( $request ) {
		$response = DatasourceCrud::get_item_by_uuid( DatasourceCrud::get_data_sources(), $request->get_param( 'uuid' ) );
		return rest_ensure_response( $response );
	}

	public function update_item( $request ) {
		$item = DatasourceCrud::update_item_by_uuid( $request->get_param( 'uuid' ), $request->get_json_params() );
		return rest_ensure_response( $item );
	}

	public function delete_item( $request ) {
		$result = DatasourceCrud::delete_item_by_uuid( $request->get_param( 'uuid' ) );
		return rest_ensure_response( $result );
	}

	public function item_slug_conflicts( $request ) {
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

	public function get_item_permissions_check( $request ) {
		return current_user_can( 'manage_options' );
	}

	public function get_items_permissions_check( $request ) {
		return current_user_can( 'manage_options' );
	}

	public function create_item_permissions_check( $request ) {
		return current_user_can( 'manage_options' );
	}

	public function update_item_permissions_check( $request ) {
		return current_user_can( 'manage_options' );
	}

	public function delete_item_permissions_check( $request ) {
		return current_user_can( 'manage_options' );
	}

	public function item_slug_conflicts_permissions_check( $request ) {
		return current_user_can( 'manage_options' );
	}
}
