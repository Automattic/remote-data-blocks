import type { Page } from '@playwright/test';
import type { Editor } from '@wordpress/e2e-test-utils-playwright';

export const GARDEN_BLOCK = 'remote-data-blocks/bindings-garden';
export const STATIC_CARD = 'rdb-bindings-demo/static-card';
export const STOCK_CARD = 'rdb-bindings-demo/stock-card';

interface FixtureBlock {
	name: string;
	attributes?: Record< string, unknown >;
	innerBlocks?: FixtureBlock[];
}

interface EditorWindow extends Window {
	wp: {
		apiFetch: ( options: Record< string, unknown > ) => Promise< RemoteDataApiResponse >;
		data: {
			select: ( name: string ) => {
				getSettings: () => {
					__experimentalBlockBindingsSupportedAttributes?: Record< string, string[] >;
				};
				getClientIdsWithDescendants: () => string[];
				getBlockName: ( clientId: string ) => string;
				getCurrentPostId: () => number;
			};
			dispatch: ( name: string ) => { selectBlock: ( clientId: string ) => void };
		};
	};
	REMOTE_DATA_BLOCKS: { rest_url: string };
	RDB_BINDINGS_DEMO: { wordpressVersion: string };
}

/** Fetch real local query results, then insert an unbound authoring template. */
export async function insertBindingsGarden(
	page: Page,
	editor: Editor,
	innerBlocks: FixtureBlock[]
) {
	const remoteData = await page.evaluate( async blockName => {
		const browser = window as unknown as EditorWindow;
		const response = await browser.wp.apiFetch( {
			url: browser.REMOTE_DATA_BLOCKS.rest_url,
			method: 'POST',
			data: {
				block_name: blockName,
				post_id: browser.wp.data.select( 'core/editor' ).getCurrentPostId(),
				query_key: 'display',
				query_inputs: [ {} ],
			},
		} );
		if ( response.status !== 200 || ! response.body ) {
			throw new Error( `Bindings fixture query failed: ${ JSON.stringify( response ) }` );
		}
		const body = response.body;
		return {
			blockName: body.block_name,
			metadata: body.metadata,
			queryInputs: body.query_inputs,
			queryKey: body.query_key,
			resultId: body.result_id,
			results: body.results,
		};
	}, GARDEN_BLOCK );
	await editor.insertBlock( { name: GARDEN_BLOCK, attributes: { remoteData }, innerBlocks } );
	return remoteData;
}

export async function selectGardenBlock( page: Page, blockName: string ) {
	await page.evaluate( name => {
		const { data } = ( window as unknown as EditorWindow ).wp;
		const store = data.select( 'core/block-editor' );
		const clientId = store
			.getClientIdsWithDescendants()
			.find( id => store.getBlockName( id ) === name );
		if ( ! clientId ) {
			throw new Error( `Fixture block not found: ${ name }` );
		}
		data.dispatch( 'core/block-editor' ).selectBlock( clientId );
	}, blockName );
	// Blocks such as Image separate their Content and Settings inspector tabs.
	await page.getByRole( 'tab', { name: 'Block', exact: true } ).click();
	const settingsTab = page.getByRole( 'tab', { name: 'Settings', exact: true } );
	if ( await settingsTab.isVisible() ) {
		await settingsTab.click();
	}
}

export function getRuntimeBindingAttributes( page: Page ) {
	return page.evaluate( () => {
		const browser = window as unknown as EditorWindow;
		return browser.wp.data.select( 'core/block-editor' ).getSettings()
			.__experimentalBlockBindingsSupportedAttributes;
	} );
}

export function getFixtureWordPressVersion( page: Page ) {
	return page.evaluate(
		() => ( window as unknown as EditorWindow ).RDB_BINDINGS_DEMO.wordpressVersion
	);
}
