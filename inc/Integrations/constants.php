<?php

namespace RemoteDataBlocks;

define( 'REMOTE_DATA_BLOCKS_AIRTABLE_SERVICE', 'airtable' );
define( 'REMOTE_DATA_BLOCKS_GITHUB_SERVICE', 'github' );
define( 'REMOTE_DATA_BLOCKS_GOOGLE_SHEETS_SERVICE', 'google-sheets' );
define( 'REMOTE_DATA_BLOCKS_SHOPIFY_SERVICE', 'shopify' );
define( 'REMOTE_DATA_BLOCKS_MOCK_SERVICE', 'mock' );

define( 'REMOTE_DATA_BLOCKS__SERVICES', [
	REMOTE_DATA_BLOCKS_AIRTABLE_SERVICE,
	REMOTE_DATA_BLOCKS_GITHUB_SERVICE,
	REMOTE_DATA_BLOCKS_GOOGLE_SHEETS_SERVICE,
	REMOTE_DATA_BLOCKS_SHOPIFY_SERVICE,
] );

const REMOTE_DATA_BLOCKS__DATASOURCE_CLASSMAP = [
	REMOTE_DATA_BLOCKS_AIRTABLE_SERVICE      => \RemoteDataBlocks\Integrations\Airtable\AirtableDatasource::class,
	REMOTE_DATA_BLOCKS_GITHUB_SERVICE        => \RemoteDataBlocks\Integrations\GitHub\GitHubDatasource::class,
	REMOTE_DATA_BLOCKS_GOOGLE_SHEETS_SERVICE => \RemoteDataBlocks\Integrations\Google\Sheets\GoogleSheetsDatasource::class,
	REMOTE_DATA_BLOCKS_SHOPIFY_SERVICE       => \RemoteDataBlocks\Integrations\Shopify\ShopifyDatasource::class,
	REMOTE_DATA_BLOCKS_MOCK_SERVICE          => \RemoteDataBlocks\Tests\Mocks\MockDatasource::class,
];
