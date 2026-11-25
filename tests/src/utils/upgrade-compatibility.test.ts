import { describe, expect, it } from 'vitest';

import fixtures from '../../fixtures/compatibility/saved-remote-data.json';
import { createQueryInputsFromRemoteDataResults, migrateRemoteData } from '@/utils/remote-data';

describe( 'saved remote data compatibility', () => {
	it.each( fixtures )( 'preserves values and query inputs for $name', fixture => {
		const migrated = migrateRemoteData( fixture.remoteData as unknown as RemoteData );
		expect( migrated?.queryInputs ).toEqual( [ { id: 'saved-row' } ] );
		expect( migrated?.results ).toHaveLength( 1 );
		expect( createQueryInputsFromRemoteDataResults( migrated!.results! ) ).toEqual( [
			fixture.values,
		] );
		expect( migrateRemoteData( migrated ) ).toEqual( migrated );
	} );

	it( 'keeps existing descriptors and plural inputs when singular inputs are also saved', () => {
		const saved = { ...fixtures[ 2 ]!.remoteData, queryInput: { id: 'obsolete-row' } };
		const migrated = migrateRemoteData( saved as unknown as RemoteData );
		expect( migrated?.queryInputs ).toEqual( [ { id: 'saved-row' } ] );
		expect( migrated?.results ).toEqual( saved.results );
	} );
} );
