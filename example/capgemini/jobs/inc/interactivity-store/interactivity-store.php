<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\Capgemini\Jobs;

defined( 'ABSPATH' ) || exit();

use RemoteDataBlocks\Editor\DataBinding\BlockBindings;
use RemoteDataBlocks\Editor\BlockManagement\ConfigStore;
use RemoteDataBlocks\REST\RemoteDataController;

class InteractivityStore {
	public static function get_initial_state(): array {
		$block_name = ConfigStore::get_block_name( 'Job Search Results' );
		$rest_url = RemoteDataController::get_url();

		// TODO: Need to support the other input variables instead of hardcoding.
		$initial_state = [
			'activeFilters' => [],
			'blockName' => $block_name,
			'countryCode' => 'gb-en',
			'page' => 1,
			'searchTerms' => get_query_var( 'search' ) ?? '',
			'size' => 10,
			'restUrl' => $rest_url,
		];

		$initial_state['jobs'] = self::get_jobs( $initial_state );
		$initial_state['filterGroups'] = self::get_filter_groups( $initial_state );

		return $initial_state;
	}

	private static function get_jobs( array $state ): array {
		$block_context = [
			'blockName' => $state['blockName'],
			'queryInput' => [
				'country_code' => $state['countryCode'],
				'page' => $state['page'],
				'search' => $state['searchTerms'],
				'size' => $state['size'],
			],
		];

		$raw_results = BlockBindings::execute_query( $block_context, 'interactivity-initial-state' );
		if ( is_wp_error( $raw_results ) || is_null( $raw_results ) ) {
			return [];
		}

		return array_map( function ( $job ) {
			return [
				'title' => $job['result']['title']['value'] ?? '',
			];
		}, $raw_results['results'] );
	}

	private static function get_filter_groups( array $state ): array {
		$block_context = [
			'blockName' => $state['blockName'],
			'queryInput' => [
				'country_code' => $state['countryCode'],
				'search' => $state['searchTerms'],
			],
			'queryKey' => __NAMESPACE__ . '\\CapgeminiJobFiltersQuery',
		];

		$raw_results = BlockBindings::execute_query( $block_context, 'interactivity-initial-state' );

		if ( is_wp_error( $raw_results ) || is_null( $raw_results ) ) {
			return [];
		}

		$filter_groups = array_map( function ( $filter_group ) {
			$filters = json_decode( $filter_group['result']['items']['value'], true );
			if ( empty( $filters ) ) {
				$filters = [];
			}

			return [
				'filters' => array_map( function ( $filter ) use ( $filter_group ) {
					$title = $filter['value'] ?? '';
					$type = $filter_group['result']['type']['value'] ?? '';

					return [
						'id' => sprintf( '%s_%s', $type, $title ),
						'count' => $filter['count'] ?? 0,
						'title' => $title,
						'type' => $type,
					];
				}, $filters ),
				'title' => $filter_group['result']['title']['value'] ?? $filter_group['result']['type']['value'] ?? '',
				'type' => $filter_group['result']['type']['value'] ?? '',
			];
		}, $raw_results['results'] );

		return array_filter( $filter_groups, function ( $filter_group ) {
			return count( $filter_group['filters'] ) > 1;
		} );
	}

	public static function get_store_name(): string {
		return 'remote-data-blocks/capgemini-jobs';
	}
}
