import { act, cleanup, render } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import DataSourceList from '@/data-sources/DataSourceList';

import type { View } from '@wordpress/dataviews/wp';

const trackSearch = vi.fn();
let dataViewsProps: { view: View; onChangeView: ( view: View ) => void } | null = null;

vi.mock( '@/data-sources/hooks/useDataSourcesSearchTracking', () => ( {
	useDataSourcesSearchTracking: () => trackSearch,
} ) );

vi.mock( '@/data-sources/hooks/useDataSources', () => ( {
	useDataSources: () => ( {
		dataSources: [
			{ uuid: '1', service: 'airtable', config_source: 'storage', service_config: {} },
		],
		loadingDataSources: false,
		deleteDataSource: vi.fn(),
		deleteMultipleDataSources: vi.fn(),
		fetchDataSources: vi.fn(),
		getDataSourceSnippet: vi.fn(),
		addDataSource: vi.fn(),
		showSnackbar: vi.fn(),
	} ),
} ) );

vi.mock( '@/settings/hooks/useSettingsNav', () => ( {
	useSettingsContext: () => ( { pushState: vi.fn() } ),
} ) );

describe( 'DataSourceList search tracking', () => {
	beforeEach( () => {
		dataViewsProps = null;
		trackSearch.mockClear();
		( window as unknown as Record< string, unknown > ).LockedPrivateDataViews = {
			DataViews: ( props: typeof dataViewsProps ) => {
				dataViewsProps = props;
				return null;
			},
			filterSortAndPaginate: ( data: unknown[] ) => ( {
				data,
				paginationInfo: { totalItems: data.length, totalPages: 1 },
			} ),
		};
	} );

	afterEach( cleanup );

	function changeView( change: Partial< View > ): void {
		act( () => {
			dataViewsProps?.onChangeView( { ...dataViewsProps.view, ...change } );
		} );
	}

	it( 'passes a changed search to the search tracking', () => {
		render( <DataSourceList /> );

		changeView( { search: 'air' } );

		expect( trackSearch ).toHaveBeenCalledTimes( 1 );
		expect( trackSearch ).toHaveBeenCalledWith( 'air' );
		expect( dataViewsProps?.view.search ).toBe( 'air' );
	} );

	it( 'does not track a view change that leaves the search alone', () => {
		render( <DataSourceList /> );

		changeView( { page: 2 } );

		expect( trackSearch ).not.toHaveBeenCalled();
		expect( dataViewsProps?.view.page ).toBe( 2 );
	} );
} );
