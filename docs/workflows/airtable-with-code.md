# Create an Airtable remote data block

This page will walk you through connecting an [Airtable](https://airtable.com/) data source, registering a remote data block to display table records, and customizing the styling of that block. It will require you to commit code to a WordPress theme or plugin. If you have not yet installed and activated the Remote Data Blocks plugin, visit [Getting Started](https://remotedatablocks.com/getting-started/).

## Base and personal access token

First, identify an Airtable base and table that you would like to use as a data source. This example uses a base created from the default [“Event planning” template](https://www.airtable.com/templates/event-planning/exppdJtYjEgfmd6Sq), accessible from the Airtable home screen after logging in. We will target the “Schedule” table from that base.

<p><img width="375" alt="airtable-template" src="https://github.com/user-attachments/assets/a5be04c6-d72c-4cf2-9e62-814af54f9a35"></p>

Next, [create a personal access token](https://airtable.com/create/tokens) that has the `data.records:read` and `schema.bases:read` scopes and has access to the base or bases you wish to use.

<p><img width="939" alt="create-pat" src="https://github.com/user-attachments/assets/16b43ea3-ebf9-4904-8c65-a3040de902d4"></p>

This personal access token is a secret that should be provided to your application securely. On WordPress VIP, we recommend using [environment variables](https://docs.wpvip.com/infrastructure/environments/manage-environment-variables/) to provide this token. The code in this example assumes that the token has been provided securely via a constant named `AIRTABLE_EVENTS_ACCESS_TOKEN`.

## Define the data source

In a file in your theme code, create a data source that provides the personal access token, the Airtable base ID, and the table ID (see ["Finding Airtable IDs"](https://support.airtable.com/docs/finding-airtable-ids#finding-base-url-ids)). The Remote Data Blocks plugin provides an AirtableDataSource class that makes this easier than defining a data source from scratch [link tk]:

```php
/* register-conference-event-block.php */

use RemoteDataBlocks\Integrations\Airtable\AirtableDataSource;

$access_token = AIRTABLE_EVENTS_ACCESS_TOKEN;
$base_id      = 'base-id';
$table_id     = 'table-id';

$airtable_data_source = new AirtableDataSource( $access_token, $base_id, $table_id );
```

This data source provides basic details needed to communicate with the Airtable API.

## Define a query

Next, define a query that describes the data that you want to extract from the data source—in this example, a single record from the table.

```php
/* class-airtable-get-event-query.php */

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;

class AirtableGetEventQuery extends HttpQueryContext {
	public function get_input_schema(): array {
		return [
			'record_id' => [
				'name' => 'Record ID',
				'type' => 'id',
			],
		];
	}

	public function get_output_schema(): array {
		return [
			'is_collection' => false,
			'mappings'      => [
				'id'       => [
					'name' => 'Record ID',
					'path' => '$.id',
					'type' => 'id',
				],
				'title'    => [
					'name' => 'Title',
					'path' => '$.fields.Activity',
					'type' => 'string',
				],
				'location' => [
					'name' => 'Location',
					'path' => '$.fields.Location',
					'type' => 'string',
				],
				'type'     => [
					'name' => 'Type',
					'path' => '$.fields.Type',
					'type' => 'string',
				],
			],
		];
	}

	/**
	 * Airtable API endpoint for fetching a single table record.
	 */
	public function get_endpoint( array $input_variables ): string {
		return $this->get_data_source()->get_endpoint() . '/' . $input_variables['record_id'];
	}
}
```

See Defining a query [link tk] for more information on input variables, output variables, and other query properties. In short, this query describes what input it needs (a record ID) and the data it returns (ID, title, location, and type). Note that the [Airtable "Get record" API endpoint](https://airtable.com/developers/web/api/get-record) includes the record ID in the URL, so you must override the `get_endpoint` method to build the URL at request time.

## Register a block

Now that you have a data source and query defined, you can register a WordPress block to display your remote data. Here's the full example:

```php
/* register-conference-event-block.php */

use RemoteDataBlocks\Integrations\Airtable\AirtableDataSource;

// AirtableGetEventQuery
require_once __DIR__ . '/class-airtable-get-event-query.php';

function register_conference_event_block() {
	$block_name   = 'Conference Event';
	$access_token = AIRTABLE_EVENTS_ACCESS_TOKEN;
	$base_id      = 'base-id';
	$table_id     = 'table-id';

	$data_source      = new AirtableDataSource( $access_token, $base_id, $table_id );
	$get_event_query  = new AirtableGetEventQuery( $data_source );

	\register_remote_data_block( $block_name, $get_event_query );
}

add_action( 'init', __NAMESPACE__ . '\\register_conference_event_block' );
```

That's it! The `register_remote_data_block` function takes care of registering the block in the Block Editor and providing UI to manage the block.

## Insert the block

Open a post for editing and select the block in the Block Inserter using the block name you provided ("Conference Event" in this example).

https://github.com/user-attachments/assets/e37e5348-9bee-47bf-bebb-f7977e53f139

The default experience is basic. Since the query requires a record ID as input, the block provides a form to enter it manually. After the record is loaded, we can select a pattern to display the data; the plugin provides a simple, default pattern to use out-of-the-box.

Next, let's work to make this default experience better.

## Registering a list query

Instead of requiring manual input of a record ID, you can enhance your remote data block to allow users to select a record from a list. Do this by creating and registering a list query:

```php
/* class-airtable-list-events-query.php */

use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;

class AirtableListEventsQuery extends HttpQueryContext {
	public function get_output_schema(): array {
		return [
			'root_path'     => '$.records[*]',
			'is_collection' => true,
			'mappings'      => [
				'record_id'       => [
					'name' => 'Record ID',
					'path' => '$.id',
					'type' => 'id',
				],
				'title'    => [
					'name' => 'Title',
					'path' => '$.fields.Activity',
					'type' => 'string',
				],
				'location' => [
					'name' => 'Location',
					'path' => '$.fields.Location',
					'type' => 'string',
				],
				'type'     => [
					'name' => 'Type',
					'path' => '$.fields.Type',
					'type' => 'string',
				],
			],
		];
	}

	public function get_query_name(): string {
		return 'List events';
	}
}
```

This query accepts no input and returns a collection of events. **Important:** Since this query's output will be used as input for `AirtableGetEventQuery`, it needs to provide the input variables expected by that query (e.g., `record_id`). If it doesn't, the Remote Data Blocks Plugin will flag it as an error.

Here's an updated example that registers this list query:

```php
/* register-conference-event-block.php */

use RemoteDataBlocks\Integrations\Airtable\AirtableDataSource;

// AirtableGetEventQuery
require_once __DIR__ . '/class-airtable-get-event-query.php';

// AirtableListEventsQuery
require_once __DIR__ . '/class-airtable-list-events-query.php';

function register_conference_event_block() {
	$block_name.  = 'Conference Event';
	$access_token = AIRTABLE_EVENTS_ACCESS_TOKEN;
	$base_id      = 'base-id';
	$table_id     = 'table-id';

	$data_source      = new AirtableDataSource( $access_token, $base_id, $table_id );
	$get_event_query  = new AirtableGetEventQuery( $data_source );

	\register_remote_data_block( $block_name, $get_event_query );

	$list_events_query = new AirtableListEventsQuery( $data_source );
	\register_remote_data_list_query( $block_name, $airtable_list_events_query );
}

add_action( 'init', __NAMESPACE__ . '\\register_conference_event_block' );
```

The `register_remote_data_list_query` function registers `AirtableListEventsQuery` against the "Conference Event" block. This lets the Remote Data Blocks plugin know that it can be used to generate a list of items for the user to pick from:

https://github.com/user-attachments/assets/67f22710-b1bd-4f2c-a410-2e20fe27b348

## Custom patterns and styling

You can improve upon the default appearance of the block by creating your own patterns. Patterns can be associated with a remote data block in the "Pattern" settings in the sidebar of the pattern editor. Once associated with a remote data block, patterns will appear in the pattern selection modal. The Remote Data Blocks plugin supports both synced and unsynced patterns.

https://github.com/user-attachments/assets/358d9d40-557b-4f39-b943-ed73d6f18adb

Alternatively, you can alter the style of a remote data block using `theme.json` and / or custom stylesheets. See the [example child theme](https://github.com/Automattic/remote-data-blocks/tree/trunk/example/theme) in the Remote Data Blocks GitHub repository for more details.

## Code reference

Check out [a working example](https://github.com/Automattic/remote-data-blocks/tree/trunk/example/airtable/events) of the concepts above in the Remote Data Blocks GitHub repository and feel free to open an issue if you run into any difficulty when registering or customizing your remote data blocks.
