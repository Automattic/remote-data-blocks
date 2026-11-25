import { fireEvent, render, screen } from '@testing-library/react';
import { createElement } from 'react';
import { assert, describe, expect, it, vi } from 'vitest';

import fixtures from '../../fixtures/compatibility/saved-remote-data.json';
import { FieldSelectionFromAvailableBindings } from '@/block-editor/format-types/inline-binding/components/InlineBindingSelection';
import { createQueryInputsFromRemoteDataResults, migrateRemoteData } from '@/utils/remote-data';

vi.mock( '@/blocks/remote-data-container/hooks/useRemoteData', () => ( {
	useRemoteData: vi.fn(),
} ) );
vi.mock( '@/utils/localized-block-data', async importOriginal => ( {
	...( await importOriginal< typeof import('@/utils/localized-block-data') >() ),
	getBlockAvailableBindings: () => ( { title: { name: 'Garden title', type: 'title' } } ),
} ) );

describe( 'saved remote data compatibility', () => {
	it.each( fixtures )( 'preserves values and query inputs for $name', fixture => {
		const migrated = migrateRemoteData( fixture.remoteData as unknown as RemoteData );
		expect( migrated?.queryInputs ).toEqual( [ { id: 'saved-row' } ] );
		expect( migrated?.results ).toHaveLength( 1 );
		expect( createQueryInputsFromRemoteDataResults( migrated?.results ?? [] ) ).toEqual( [
			fixture.values,
		] );
		expect( migrateRemoteData( migrated ) ).toEqual( migrated );
	} );

	it( 'selects the title of the historical row from the existing-items field chooser', () => {
		const fixture = fixtures.find(
			item => item.name === 'legacy scalar fields named result and uuid'
		);
		assert.isDefined( fixture );
		const remoteData = migrateRemoteData( fixture.remoteData as unknown as RemoteData );
		assert.isDefined( remoteData );
		const onSelectField = vi.fn();
		render( createElement( FieldSelectionFromAvailableBindings, { remoteData, onSelectField } ) );
		fireEvent.click( screen.getByRole( 'menuitem', { name: 'Garden title: Saved garden' } ) );
		expect( onSelectField ).toHaveBeenCalledWith(
			expect.objectContaining( { selectedField: 'title', remoteData } ),
			'Saved garden'
		);
	} );

	it( 'keeps existing descriptors and plural inputs when singular inputs are also saved', () => {
		const descriptorFixture = fixtures.find(
			item => item.name === 'typed descriptors after bulk selection'
		);
		assert.isDefined( descriptorFixture );
		const saved = { ...descriptorFixture.remoteData, queryInput: { id: 'obsolete-row' } };
		const migrated = migrateRemoteData( saved as unknown as RemoteData );
		expect( migrated?.queryInputs ).toEqual( [ { id: 'saved-row' } ] );
		expect( migrated?.results ).toEqual( saved.results );
	} );

	it( 'preserves empty result maps and numeric IDs', () => {
		const saved = {
			results: [
				{ uuid: 0, result: {} },
				{ uuid: '1', result: [] },
			],
		};
		const migrated = migrateRemoteData( saved as unknown as RemoteData );
		expect( migrated?.results ).toEqual( saved.results );
	} );

	it( 'keeps sibling fields when an object-valued result is not a descriptor map', () => {
		const row = {
			result: { status: { name: 'Status', type: 'string' }, detail: null },
			uuid: '754b3408-1014-4863-b072-b005181ff92f',
			title: 'Saved garden',
		};
		const saved = { results: [ row ] };
		const migrated = migrateRemoteData( saved as unknown as RemoteData );
		expect( createQueryInputsFromRemoteDataResults( migrated?.results ?? [] ) ).toEqual( [ row ] );
	} );
} );
