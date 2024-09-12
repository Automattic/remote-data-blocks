<?php

namespace RemoteDataBlocks\REST;

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
			'/' . $this->rest_base . '/(?P<uid>[\w-]+)',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_item' ],
				'permission_callback' => [ $this, 'get_item_permissions_check' ],
				'args'                => [
					'uid' => [
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
			]
		);

		// update_item
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<uid>[\w-]+)',
			[
				'methods'             => 'PUT',
				'callback'            => [ $this, 'update_item' ],
				'permission_callback' => [ $this, 'update_item_permissions_check' ],
			]
		);

		// delete_item
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<uid>[\w-]+)',
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
		$item = DatasourceCRUD::register_new_data_source( $request->get_json_params() );
		return rest_ensure_response( $item );
	}

	public function get_items( $request ) {
		return rest_ensure_response( DatasourceCRUD::get_data_sources() );
	}

	public function get_item( $request ) {
		$response = DatasourceCRUD::get_item_by_uid( DatasourceCRUD::get_data_sources(), $request->get_param( 'uid' ) );
		return rest_ensure_response( $response );
	}

	public function update_item( $request ) {
		$item = DatasourceCRUD::update_item_by_uid( DatasourceCRUD::get_data_sources(), $request->get_param( 'uid' ), $request->get_json_params() );
		return rest_ensure_response( $item );
	}

	public function delete_item( $request ) {
		$result = DatasourceCRUD::delete_item_by_uid( DatasourceCRUD::get_data_sources(), $request->get_param( 'uid' ) );
		return rest_ensure_response( $result );
	}

	/**
	 * @deprecated Hew will remove. Or else.
	 */
	public function item_slug_conflicts( $request ) {
		_deprecated_function( __METHOD__, '6.6.1' );
		return \rest_ensure_response( [ 'exists' => false ] );
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
