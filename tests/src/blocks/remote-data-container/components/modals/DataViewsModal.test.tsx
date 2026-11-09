import { act, cleanup, render } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { DataViewsModal } from '@/blocks/remote-data-container/components/modals/DataViewsModal';
import { sendTracksEvent } from '@/blocks/remote-data-container/utils/tracks';

let itemListProps: { onSelect?: ( results: RemoteDataApiResult[] ) => void } | null = null;

vi.mock( '@/blocks/remote-data-container/utils/tracks', () => ( {
	sendTracksEvent: vi.fn(),
} ) );

vi.mock( '@/blocks/remote-data-container/components/item-list/ItemList', () => ( {
	ItemList: ( props: typeof itemListProps ) => {
		itemListProps = props;
		return null;
	},
} ) );

vi.mock( '@/blocks/remote-data-container/hooks/useModalState', () => ( {
	useModalState: () => ( { close: vi.fn(), isOpen: true, open: vi.fn() } ),
} ) );

vi.mock( '@/blocks/remote-data-container/hooks/useRemoteData', () => ( {
	useRemoteData: () => ( { data: { results: [] }, loading: false } ),
} ) );

vi.mock( '@/utils/localized-block-data', () => ( {
	getBlockConfig: () => undefined,
	getBlockDataSourceType: () => 'airtable',
} ) );

vi.mock( '@/utils/remote-data', () => ( {
	createQueryInputsFromRemoteDataResults: () => [],
} ) );

describe( 'DataViewsModal tracking', () => {
	beforeEach( () => {
		itemListProps = null;
		vi.mocked( sendTracksEvent ).mockClear();
	} );

	afterEach( cleanup );

	it( 'records how many items were selected', () => {
		render( <DataViewsModal blockName="test/block" onSelect={ vi.fn() } queryKey="list" /> );

		act( () => {
			itemListProps?.onSelect?.( [
				{ uuid: 'a' },
				{ uuid: 'b' },
				{ uuid: 'c' },
			] as RemoteDataApiResult[] );
		} );

		expect( sendTracksEvent ).toHaveBeenCalledTimes( 1 );
		expect( sendTracksEvent ).toHaveBeenCalledWith( 'add_block', {
			action: 'select_item',
			selected_option: 'search_from_list',
			data_source_type: 'airtable',
			selected_count: 3,
		} );
	} );
} );
