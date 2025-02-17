import { render, waitFor } from '@testing-library/react';
import { describe, expect, it, vi, beforeEach } from 'vitest';

import { sendTracksEvent } from '@/blocks/remote-data-container/utils/tracks';
import DataSourceList from '@/data-sources/DataSourceList';
import { ConfigSource } from '@/data-sources/constants';
import { useDataSources } from '@/data-sources/hooks/useDataSources';
import { useSettingsContext } from '@/settings/hooks/useSettingsNav';

import type {
	DataSourceConfig,
	HttpServiceConfig,
	ShopifyServiceConfig,
	AirtableServiceConfig,
} from '@/data-sources/types';

vi.mock( '@/blocks/remote-data-container/utils/tracks', () => ( {
	sendTracksEvent: vi.fn(),
} ) );

vi.mock( '@/data-sources/hooks/useDataSources' );
vi.mock( '@/settings/hooks/useSettingsNav' );

describe( 'DataSourceList', () => {
	const mockDataSources: DataSourceConfig[] = [
		{
			uuid: '1',
			service: 'generic-http',
			config_source: ConfigSource.CODE,
			service_config: {
				__version: 1,
				display_name: 'HTTP Source',
				endpoint: 'https://api.example.com',
				auth: { type: 'none' },
			} as HttpServiceConfig,
		},
		{
			uuid: '2',
			service: 'shopify',
			config_source: ConfigSource.STORAGE,
			service_config: {
				__version: 1,
				display_name: 'Shopify Source',
				access_token: 'token',
				store_name: 'store',
				enable_blocks: true,
			} as ShopifyServiceConfig,
		},
		{
			uuid: '3',
			service: 'airtable',
			config_source: ConfigSource.CONSTANTS,
			service_config: {
				__version: 1,
				display_name: 'Airtable Source',
				access_token: 'token',
				base: {
					id: 'base',
					name: 'Base Name',
					tables: [],
				},
				tables: [],
				enable_blocks: true,
			} as AirtableServiceConfig,
		},
	];

	beforeEach( () => {
		vi.mocked( useDataSources ).mockReturnValue( {
			dataSources: [],
			loadingDataSources: false,
			deleteDataSource: vi.fn(),
			deleteMultipleDataSources: vi.fn(),
			fetchDataSources: vi.fn(),
			getDataSourceSnippet: vi.fn(),
			addDataSource: vi.fn(),
			showSnackbar: vi.fn(),
			canUseDisplayName: vi.fn(),
			updateDataSource: vi.fn(),
			onSave: vi.fn(),
		} );

		vi.mocked( useSettingsContext ).mockReturnValue( {
			pushState: vi.fn(),
			screen: 'dataSourceList',
			goToMainScreen: vi.fn(),
		} );

		vi.clearAllMocks();
	} );

	it( 'should not track view_data_sources on initial render with empty data', () => {
		render( <DataSourceList /> );
		expect( sendTracksEvent ).not.toHaveBeenCalled();
	} );

	it( 'should not track view_data_sources when loading state changes to true', () => {
		const { rerender } = render( <DataSourceList /> );

		vi.mocked( useDataSources ).mockReturnValue( {
			dataSources: [],
			loadingDataSources: true,
			deleteDataSource: vi.fn(),
			deleteMultipleDataSources: vi.fn(),
			fetchDataSources: vi.fn(),
			getDataSourceSnippet: vi.fn(),
			addDataSource: vi.fn(),
			showSnackbar: vi.fn(),
			canUseDisplayName: vi.fn(),
			updateDataSource: vi.fn(),
			onSave: vi.fn(),
		} );

		rerender( <DataSourceList /> );

		expect( sendTracksEvent ).not.toHaveBeenCalled();
	} );

	it( 'should track view_data_sources when data is loaded', async () => {
		const { rerender } = render( <DataSourceList /> );

		// Simulate loading state
		vi.mocked( useDataSources ).mockReturnValue( {
			dataSources: [],
			loadingDataSources: true,
			deleteDataSource: vi.fn(),
			deleteMultipleDataSources: vi.fn(),
			fetchDataSources: vi.fn(),
			getDataSourceSnippet: vi.fn(),
			addDataSource: vi.fn(),
			showSnackbar: vi.fn(),
			canUseDisplayName: vi.fn(),
			updateDataSource: vi.fn(),
			onSave: vi.fn(),
		} );

		rerender( <DataSourceList /> );

		// Simulate loaded state with data
		vi.mocked( useDataSources ).mockReturnValue( {
			dataSources: mockDataSources,
			loadingDataSources: false,
			deleteDataSource: vi.fn(),
			deleteMultipleDataSources: vi.fn(),
			fetchDataSources: vi.fn(),
			getDataSourceSnippet: vi.fn(),
			addDataSource: vi.fn(),
			showSnackbar: vi.fn(),
			canUseDisplayName: vi.fn(),
			updateDataSource: vi.fn(),
			onSave: vi.fn(),
		} );

		rerender( <DataSourceList /> );

		await waitFor( () => {
			expect( sendTracksEvent ).toHaveBeenCalledTimes( 1 );
			expect( sendTracksEvent ).toHaveBeenCalledWith( 'view_data_sources', {
				total_data_sources_count: 3,
				code_configured_data_sources_count: 1,
				ui_configured_data_sources_count: 1,
				constants_configured_data_sources_count: 1,
			} );
		} );
	} );

	it( 'should not track view_data_sources multiple times for the same data load', async () => {
		const { rerender } = render( <DataSourceList /> );

		// Simulate loading state
		vi.mocked( useDataSources ).mockReturnValue( {
			dataSources: [],
			loadingDataSources: true,
			deleteDataSource: vi.fn(),
			deleteMultipleDataSources: vi.fn(),
			fetchDataSources: vi.fn(),
			getDataSourceSnippet: vi.fn(),
			addDataSource: vi.fn(),
			showSnackbar: vi.fn(),
			canUseDisplayName: vi.fn(),
			updateDataSource: vi.fn(),
			onSave: vi.fn(),
		} );

		rerender( <DataSourceList /> );

		// Simulate loaded state with data
		vi.mocked( useDataSources ).mockReturnValue( {
			dataSources: mockDataSources,
			loadingDataSources: false,
			deleteDataSource: vi.fn(),
			deleteMultipleDataSources: vi.fn(),
			fetchDataSources: vi.fn(),
			getDataSourceSnippet: vi.fn(),
			addDataSource: vi.fn(),
			showSnackbar: vi.fn(),
			canUseDisplayName: vi.fn(),
			updateDataSource: vi.fn(),
			onSave: vi.fn(),
		} );

		rerender( <DataSourceList /> );

		// Rerender with same data
		rerender( <DataSourceList /> );

		await waitFor( () => {
			expect( sendTracksEvent ).toHaveBeenCalledTimes( 1 );
		} );
	} );
} );
