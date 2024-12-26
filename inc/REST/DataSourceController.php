<?php declare(strict_types = 1);

namespace RemoteDataBlocks\REST;

use RemoteDataBlocks\Analytics\TracksAnalytics;
use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;
use RemoteDataBlocks\WpdbStorage\DataSourceCrud;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit();

class DataSourceController extends WP_REST_Controller {
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
				'methods' => 'GET',
				'callback' => [ $this, 'get_items' ],
				'permission_callback' => [ $this, 'get_items_permissions_check' ],
			]
		);

		// get_item
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<uuid>[\w-]+)',
			[
				'methods' => 'GET',
				'callback' => [ $this, 'get_item' ],
				'permission_callback' => [ $this, 'get_item_permissions_check' ],
				'args' => [
					'uuid' => [
						'type' => 'string',
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
				'methods' => 'POST',
				'callback' => [ $this, 'create_item' ],
				'permission_callback' => [ $this, 'create_item_permissions_check' ],
				'args' => [
					'service' => [
						'type' => 'string',
						'required' => true,
						'enum' => REMOTE_DATA_BLOCKS__SERVICES,
					],
					'service_config' => [
						'type' => 'object',
						'required' => true,
					],
				],
			]
		);

		// update_item
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<uuid>[\w-]+)',
			[
				'methods' => 'PUT',
				'callback' => [ $this, 'update_item' ],
				'permission_callback' => [ $this, 'update_item_permissions_check' ],
				'args' => [
					'uuid' => [
						'type' => 'string',
						'required' => true,
					],
				],
			]
		);

		// delete_item
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<uuid>[\w-]+)',
			[
				'methods' => 'DELETE',
				'callback' => [ $this, 'delete_item' ],
				'permission_callback' => [ $this, 'delete_item_permissions_check' ],
				'args' => [
					'uuid' => [
						'type' => 'string',
						'required' => true,
					],
				],
			]
		);
	}

	/**
	 * Creates a new item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {
		$data_source_properties = $request->get_json_params();
		$item = DataSourceCrud::create_config( $data_source_properties );

		TracksAnalytics::record_event( 'remotedatablocks_data_source_interaction', array_merge( [
			'data_source_type' => $data_source_properties['service'],
			'action' => 'add',
		], $this->get_data_source_interaction_track_props( $data_source_properties ) ) );

		return rest_ensure_response( $item );
	}

	/**
	 * Retrieves a collection of items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$code_configured_data_sources = ConfigStore::get_data_sources_as_array();
		$ui_configured_data_sources = DataSourceCrud::get_configs();

		/**
		 * Quick and dirty de-duplication of data sources. If the data source does
		 * not have a UUID (because it is registered in code), we generate an
		 * identifier based on the display name and service name.
		 *
		 * UI configured data sources take precedence over code configured ones
		 * here due to the ordering of the two arrays passed to array_reduce.
		 */
		$data_sources = array_values( array_reduce(
			array_merge( $code_configured_data_sources, $ui_configured_data_sources ),
			function ( $acc, $item ) {
				$identifier = $item['uuid'] ?? md5( sprintf( '%s_%s', $item['service_config']['display_name'], $item['service'] ) );
				$acc[ $identifier ] = $item;
				return $acc;
			},
			[]
		) );

		// Tracks Analytics. Only once per day to reduce noise.
		$track_transient_key = 'remotedatablocks_view_data_sources_tracked';
		if ( ! get_transient( $track_transient_key ) ) {
			$code_configured_data_sources_count = count( $code_configured_data_sources );
			$ui_configured_data_sources_count = count( $ui_configured_data_sources );

			TracksAnalytics::record_event( 'remotedatablocks_view_data_sources', [
				'total_data_sources_count' => $code_configured_data_sources_count + $ui_configured_data_sources_count,
				'code_configured_data_sources_count' => $code_configured_data_sources_count,
				'ui_configured_data_sources_count' => $ui_configured_data_sources_count,
			] );
			set_transient( $track_transient_key, true, DAY_IN_SECONDS );
		}

		return rest_ensure_response( $data_sources );
	}

	/**
	 * Retrieves a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$response = DataSourceCrud::get_config_by_uuid( $request->get_param( 'uuid' ) );
		return rest_ensure_response( $response );
	}

	/**
	 * Updates a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {
		$data_source_properties = $request->get_json_params();
		$item = DataSourceCrud::update_config_by_uuid( $request->get_param( 'uuid' ), $data_source_properties );

		if ( is_wp_error( $item ) ) {
			return $item; // Return WP_Error if update fails
		}

		TracksAnalytics::record_event( 'remotedatablocks_data_source_interaction', array_merge( [
			'data_source_type' => $item['service'],
			'action' => 'update',
		], $this->get_data_source_interaction_track_props( $item ) ) );

		return rest_ensure_response( $item );
	}

	/**
	 * Deletes a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {
		$data_source_properties = $request->get_json_params();
		$result = DataSourceCrud::delete_config_by_uuid( $request->get_param( 'uuid' ) );

		// Tracks Analytics.
		TracksAnalytics::record_event( 'remotedatablocks_data_source_interaction', [
			'data_source_type' => $data_source_properties['service'],
			'action' => 'delete',
		] );

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

	private function get_data_source_interaction_track_props( $data_source_properties ): array {
		$props = [];

		if ( 'generic-http' === $data_source_properties['service'] ) {
			$auth = $data_source_properties['service_config']['auth'] ?? [];
			$props['authentication_type'] = $auth['type'] ?? '';
			$props['api_key_location'] = $auth['addTo'] ?? '';
		}

		return $props;
	}
}
