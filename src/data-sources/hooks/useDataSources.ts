import apiFetch from '@wordpress/api-fetch';
import { useDispatch } from '@wordpress/data';
import { useCallback, useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { store as noticesStore, NoticeStoreActions, WPNotice } from '@wordpress/notices';

import { REST_BASE_DATA_SOURCES } from '@/data-sources/constants';
import { DataSourceConfig } from '@/data-sources/types';

export const useDataSources = ( loadOnMount = true ) => {
	const [ loadingDataSources, setLoadingDataSources ] = useState< boolean >( false );
	const [ dataSources, setDataSources ] = useState< DataSourceConfig[] >( [] );
	const { createSuccessNotice, createErrorNotice } =
		useDispatch< NoticeStoreActions >( noticesStore );

	const checkDisplayNameConflict = useCallback(
		( displayName: string, uuid: string ): boolean => {
			if ( ! displayName ) {
				return false;
			}

			const existingSource = dataSources.find(
				source => source.uuid !== uuid && source.display_name === displayName
			);

			return ! existingSource;
		},
		[ dataSources ]
	);

	async function fetchDataSources() {
		setLoadingDataSources( true );
		try {
			const sources = ( await apiFetch( { path: REST_BASE_DATA_SOURCES } ) ) || [];
			setDataSources( sources as DataSourceConfig[] );
		} catch ( error ) {
			showSnackbar( 'error', __( 'Failed to load Data Sources.', 'remote-data-blocks' ) );
		}
		setLoadingDataSources( false );
	}

	async function updateDataSource( sourceConfig: DataSourceConfig ) {
		let result: DataSourceConfig;

		try {
			const data = { ...sourceConfig };
			if ( sourceConfig.newUUID && sourceConfig.newUUID !== sourceConfig.uuid ) {
				data.newUUID = sourceConfig.newUUID;
			}

			result = await apiFetch( {
				path: `${ REST_BASE_DATA_SOURCES }/${ sourceConfig.uuid }`,
				method: 'PUT',
				data,
			} );
		} catch ( error ) {
			showSnackbar( 'error', __( 'Failed to update data source.', 'remote-data-blocks' ) );
			throw error;
		}

		showSnackbar(
			'success',
			sprintf(
				__( '"%s" has been successfully updated.', 'remote-data-blocks' ),
				sourceConfig.display_name
			)
		);
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
			showSnackbar( 'error', __( 'Failed to add data source.', 'remote-data-blocks' ) );
			throw error;
		}

		showSnackbar(
			'success',
			sprintf(
				__( '"%s" has been successfully added.', 'remote-data-blocks' ),
				source.display_name
			)
		);
		return result;
	}

	async function deleteDataSource( source: DataSourceConfig ) {
		try {
			await apiFetch( {
				path: `${ REST_BASE_DATA_SOURCES }/${ source.uuid }`,
				method: 'DELETE',
				data: source,
			} );
		} catch ( error ) {
			showSnackbar( 'error', __( 'Failed to delete data source.', 'remote-data-blocks' ) );
			throw error;
		}

		showSnackbar(
			'success',
			sprintf(
				__( '"%s" has been successfully deleted.', 'remote-data-blocks' ),
				source.display_name
			)
		);
	}

	function showSnackbar( type: 'success' | 'error', message: string ): void {
		const SNACKBAR_OPTIONS: Partial< WPNotice > = {
			isDismissible: true,
		};

		switch ( type ) {
			case 'success':
				createSuccessNotice( message, { ...SNACKBAR_OPTIONS, icon: '✅' } );
				break;
			case 'error':
				createErrorNotice( message, { ...SNACKBAR_OPTIONS, icon: '❌' } );
				break;
		}
	}

	useEffect( () => {
		if ( loadOnMount ) {
			fetchDataSources().catch( console.error ); // TODO: Error handling
		}
	}, [] );

	return {
		addDataSource,
		checkDisplayNameConflict,
		dataSources,
		deleteDataSource,
		loadingDataSources,
		updateDataSource,
		fetchDataSources,
	};
};
