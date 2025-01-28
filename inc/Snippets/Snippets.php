<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Snippets;

use RemoteDataBlocks\WpdbStorage\DataSourceCrud;
use RemoteDataBlocks\Integrations\Airtable\AirtableIntegration;
use RemoteDataBlocks\Integrations\Google\Sheets\GoogleSheetsIntegration;
use RemoteDataBlocks\Integrations\Shopify\ShopifyIntegration;
use WP_Error;

class Snippets {
	public static function get_snippets( string $uuid ): array|WP_Error {
		$data_source = DataSourceCrud::get_inflated_config_by_uuid( $uuid );

		if ( is_wp_error( $data_source ) ) {
			return $data_source;
		}

		$data_source_config = $data_source->to_array();
		$service = $data_source_config['service'];

		switch ( $service ) {
			case 'shopify':
				$snippets = ShopifyIntegration::get_block_registration_snippets( $data_source_config );
				break;
			case 'airtable':
				$snippets = AirtableIntegration::get_block_registration_snippets( $data_source_config );
				break;
			case 'google-sheets':
				$snippets = GoogleSheetsIntegration::get_block_registration_snippets( $data_source_config );
				break;
			default:
				return new WP_Error( 'invalid_service', __( 'Invalid service', 'remote-data-blocks' ) );
		}

		return array_map( [ __CLASS__, 'strip_template_comments' ], $snippets );
	}

	protected static function strip_template_comments( string $content ): string {
		// Match PHPDoc blocks that contain @template tags and any preceding blank lines
		return preg_replace(
			'/\n*\/\*\*\s*\n\s*\*\s*@template-.*?\*\/\n*/s',
			"\n\n",
			$content
		);
	}
}
