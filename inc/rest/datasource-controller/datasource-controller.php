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
	}

	public function create_item( $request ) {
		$item = DatasourceCRUD::register_new_data_source( $request->get_json_params() );
		return rest_ensure_response( $item );
	}

	public function get_items( $request ) {
		return rest_ensure_response( DatasourceCRUD::get_data_sources() );
	}

	public function get_item( $request ) {
		$response = DatasourceCRUD::get_item_by_uuid( DatasourceCRUD::get_data_sources(), $request->get_param( 'uuid' ) );
		return rest_ensure_response( $response );
	}

	public function update_item( $request ) {
		$item = DatasourceCRUD::update_item_by_uuid( $request->get_param( 'uuid' ), $request->get_json_params() );
		return rest_ensure_response( $item );
	}

	public function delete_item( $request ) {
		$result = DatasourceCRUD::delete_item_by_uuid( $request->get_param( 'uuid' ) );
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
}
