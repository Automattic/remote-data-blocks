<?php declare(strict_types = 1);

namespace RemoteDataBlocks\REST;

use RemoteDataBlocks\Config\Query\QueryRegistry;
use RemoteDataBlocks\WpdbStorage\DataSourceCrud;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class QueryController
 *
 * Handles REST API endpoints for queries.
 */
class QueryController extends WP_REST_Controller {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->namespace = 'remote-data-blocks/v1';
		$this->rest_base = 'queries';
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods' => WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args' => $this->get_collection_params(),
				],
				[
					'methods' => WP_REST_Server::CREATABLE,
					'callback' => [ $this, 'create_item' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args' => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<uuid>[\\w\\-]+)',
			[
				'args' => [
					'uuid' => [
						'description' => __( 'Unique identifier for the query', 'remote-data-blocks' ),
						'type' => 'string',
					],
				],
				[
					'methods' => WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_item' ],
					'permission_callback' => [ $this, 'get_item_permissions_check' ],
					'args' => $this->get_endpoint_args_for_item_schema( WP_REST_Server::READABLE ),
				],
				[
					'methods' => WP_REST_Server::EDITABLE,
					'callback' => [ $this, 'update_item' ],
					'permission_callback' => [ $this, 'update_item_permissions_check' ],
					'args' => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				],
				[
					'methods' => WP_REST_Server::DELETABLE,
					'callback' => [ $this, 'delete_item' ],
					'permission_callback' => [ $this, 'delete_item_permissions_check' ],
					'args' => $this->get_endpoint_args_for_item_schema( WP_REST_Server::DELETABLE ),
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/data-source/(?P<data_source_uuid>[\\w\\-]+)',
			[
				'args' => [
					'data_source_uuid' => [
						'description' => __( 'UUID of the data source', 'remote-data-blocks' ),
						'type' => 'string',
					],
				],
				[
					'methods' => WP_REST_Server::READABLE,
					'callback' => [ $this, 'get_items_by_data_source' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args' => $this->get_collection_params(),
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);
	}

	/**
	 * Check if a given request has access to get items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to access queries.', 'remote-data-blocks' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return true;
	}

	/**
	 * Get a collection of items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$registry = new QueryRegistry();
		$queries = $registry->get_all_queries();

		// Filter by service if provided
		$service = $request->get_param( 'service' );
		if ( $service ) {
			$queries = array_filter( $queries, function ( $query ) use ( $service ) {
				return $query['service'] === $service;
			} );
		}

		return rest_ensure_response( array_values( $queries ) );
	}

	/**
	 * Get items by data source UUID.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items_by_data_source( $request ) {
		$data_source_uuid = $request->get_param( 'data_source_uuid' );

		// Verify data source exists
		$data_source = DataSourceCrud::get_config_by_uuid( $data_source_uuid );
		if ( is_wp_error( $data_source ) ) {
			return $data_source;
		}

		$registry = new QueryRegistry();
		$queries = $registry->get_queries_by_data_source( $data_source_uuid );

		return rest_ensure_response( array_values( $queries ) );
	}

	/**
	 * Check if a given request has access to get a specific item.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return true|WP_Error True if the request has read access for the item, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ) {
		return $this->get_items_permissions_check( $request );
	}

	/**
	 * Get one item from the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$uuid = $request->get_param( 'uuid' );
		$registry = new QueryRegistry();
		$query = $registry->get_query_by_uuid( $uuid );

		if ( is_wp_error( $query ) ) {
			return $query;
		}

		return rest_ensure_response( $query );
	}

	/**
	 * Check if a given request has access to create items.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return true|WP_Error True if the request has access to create items, WP_Error object otherwise.
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to create queries.', 'remote-data-blocks' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return true;
	}

	/**
	 * Create one item from the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {
		$config = $request->get_params();

		// Ensure data_source_uuid is provided
		if ( empty( $config['data_source_uuid'] ) ) {
			return new WP_Error(
				'missing_data_source',
				__( 'Data source UUID is required', 'remote-data-blocks' ),
				[ 'status' => 400 ]
			);
		}

		// Verify data source exists
		$data_source = DataSourceCrud::get_config_by_uuid( $config['data_source_uuid'] );
		if ( is_wp_error( $data_source ) ) {
			return $data_source;
		}

		// Set service from data source if not provided
		if ( empty( $config['service'] ) ) {
			$config['service'] = $data_source['service'];
		}

		$registry = new QueryRegistry();
		$query = $registry->register_query( $config );

		if ( is_wp_error( $query ) ) {
			return $query;
		}

		$response = rest_ensure_response( $query );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Check if a given request has access to update a specific item.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return true|WP_Error True if the request has access to update the item, WP_Error object otherwise.
	 */
	public function update_item_permissions_check( $request ) {
		return $this->create_item_permissions_check( $request );
	}

	/**
	 * Update one item from the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {
		$uuid = $request->get_param( 'uuid' );
		$config = $request->get_params();

		// Remove UUID from the payload as it's already in the path
		unset( $config['uuid'] );

		$registry = new QueryRegistry();
		$query = $registry->update_query( $uuid, $config );

		if ( is_wp_error( $query ) ) {
			return $query;
		}

		return rest_ensure_response( $query );
	}

	/**
	 * Check if a given request has access to delete a specific item.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return true|WP_Error True if the request has access to delete the item, WP_Error object otherwise.
	 */
	public function delete_item_permissions_check( $request ) {
		return $this->create_item_permissions_check( $request );
	}

	/**
	 * Delete one item from the collection.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {
		$uuid = $request->get_param( 'uuid' );

		$registry = new QueryRegistry();
		$result = $registry->delete_query( $uuid );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( [ 'deleted' => true ] );
	}

	/**
	 * Get the query schema, conforming to JSON Schema.
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		return [
			'$schema' => 'http://json-schema.org/draft-04/schema#',
			'title' => 'query',
			'type' => 'object',
			'properties' => [
				'uuid' => [
					'description' => __( 'Unique identifier for the query', 'remote-data-blocks' ),
					'type' => 'string',
					'context' => [ 'view', 'edit', 'embed' ],
					'readonly' => true,
				],
				'service' => [
					'description' => __( 'Service identifier', 'remote-data-blocks' ),
					'type' => 'string',
					'required' => true,
					'context' => [ 'view', 'edit', 'embed' ],
				],
				'data_source_uuid' => [
					'description' => __( 'UUID of the data source', 'remote-data-blocks' ),
					'type' => 'string',
					'required' => true,
					'context' => [ 'view', 'edit', 'embed' ],
				],
				'query_config' => [
					'description' => __( 'Query configuration', 'remote-data-blocks' ),
					'type' => 'object',
					'context' => [ 'view', 'edit', 'embed' ],
				],
				'__metadata' => [
					'description' => __( 'Metadata', 'remote-data-blocks' ),
					'type' => 'object',
					'readonly' => true,
					'context' => [ 'view', 'edit', 'embed' ],
				],
			],
		];
	}

	/**
	 * Get collection parameters.
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
		return [
			'service' => [
				'description' => __( 'Filter queries by service', 'remote-data-blocks' ),
				'type' => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			],
		];
	}
}