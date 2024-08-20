import apiFetch from '@wordpress/api-fetch';
import { useDispatch } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { store as noticesStore, NoticeStoreActions } from '@wordpress/notices';

import { REST_BASE_DATA_SOURCES } from '../constants';
import { DataSourceConfig } from '../types';

export const useDataSources = ( loadOnMount = true ) => {
	const [ loadingDataSources, setLoadingDataSources ] = useState< boolean >( false );
	const [ dataSources, setDataSources ] = useState< DataSourceConfig[] >( [] );
	const { createSuccessNotice, createErrorNotice } =
		useDispatch< NoticeStoreActions >( noticesStore );

	async function fetchDataSources() {
		setLoadingDataSources( true );
		try {
			const sources = ( await apiFetch( { path: REST_BASE_DATA_SOURCES } ) ) || [];
			setDataSources( sources as DataSourceConfig[] );
		} catch ( error ) {
			createErrorNotice( __( 'Failed to load Data Sources.', 'remote-data-blocks' ) );
		}
		setLoadingDataSources( false );
	}

	async function updateDataSource( source: DataSourceConfig ) {
		let result: DataSourceConfig;

		try {
			result = await apiFetch( {
				path: `${ REST_BASE_DATA_SOURCES }/${ source.uuid }`,
				method: 'PUT',
				data: source,
			} );
		} catch ( error ) {
			createErrorNotice( __( 'Failed to update Data Source.', 'remote-data-blocks' ) );
			throw error;
		}

		createSuccessNotice( __( 'Updated Data Source.', 'remote-data-blocks' ) );
		return result;
	}

	async function addDataSource( source: DataSourceConfig ) {
		let result: DataSourceConfig;

		try {
			result = await apiFetch( {
				path: REST_BASE_DATA_SOURCES,
				method: 'POST',
				data: source,
			} );
		} catch ( error ) {
			createErrorNotice( __( 'Failed to add Data Source.', 'remote-data-blocks' ) );
			throw error;
		}

		createSuccessNotice( __( 'Added Data Source.', 'remote-data-blocks' ) );
		return result;
	}

	async function deleteDataSource( uuid: string ) {
		try {
			await apiFetch( {
				path: `${ REST_BASE_DATA_SOURCES }/${ uuid }`,
				method: 'DELETE',
			} );
		} catch ( error ) {
			createErrorNotice( __( 'Failed to delete Data Source.', 'remote-data-blocks' ) );
			throw error;
		}

		createSuccessNotice( __( `Deleted Data Source: ${ uuid }`, 'remote-data-blocks' ) );
	}

	useEffect( () => {
		if ( loadOnMount ) {
			fetchDataSources().catch( console.error ); // TODO: Error handling
		}
	}, [] );

	return {
		addDataSource,
		dataSources,
		deleteDataSource,
		loadingDataSources,
		updateDataSource,
		fetchDataSources,
	};
};
