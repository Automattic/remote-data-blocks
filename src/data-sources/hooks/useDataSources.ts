import apiFetch from '@wordpress/api-fetch';
import { useDispatch } from '@wordpress/data';
import { useEffect, useState, useCallback } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { store as noticesStore, NoticeStoreActions, WPNotice } from '@wordpress/notices';

import { REST_BASE_DATA_SOURCES } from '@/data-sources/constants';
import { DataSourceConfig } from '@/data-sources/types';

export const useDataSources = ( loadOnMount = true ) => {
	const [ loadingDataSources, setLoadingDataSources ] = useState< boolean >( false );
	const [ dataSources, setDataSources ] = useState< DataSourceConfig[] >( [] );
	const { createSuccessNotice, createErrorNotice } =
		useDispatch< NoticeStoreActions >( noticesStore );
	const [ slugConflicts, setSlugConflicts ] = useState< boolean >( false );
	const [ loadingSlugConflicts, setLoadingSlugConflicts ] = useState< boolean >( false );

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

	const checkSlugConflict = useCallback(
		async ( slug: string, uuid: string = '' ) => {
			if ( slug === '' ) {
				showSnackbar( 'error', __( 'Slug should not be empty.', 'remote-data-blocks' ) );
				return;
			}

			setLoadingSlugConflicts( true );
			try {
				const conflict = await apiFetch< { exists: boolean } >( {
					path: `${ REST_BASE_DATA_SOURCES }/slug-conflicts`,
					method: 'POST',
					data: { slug, uuid },
				} );
				setSlugConflicts( conflict?.exists ?? false );
			} catch ( error ) {
				createErrorNotice( __( 'Failed to check slug availability.', 'remote-data-blocks' ) );
			}
			setLoadingSlugConflicts( false );
		},
		[ setLoadingSlugConflicts, setSlugConflicts, createErrorNotice ]
	);

	async function updateDataSource( source: DataSourceConfig ) {
		let result: DataSourceConfig;

		try {
			result = await apiFetch( {
				path: `${ REST_BASE_DATA_SOURCES }/${ source.uuid }`,
				method: 'PUT',
				data: source,
			} );
		} catch ( error ) {
			showSnackbar( 'error', __( 'Failed to update data source.', 'remote-data-blocks' ) );
			throw error;
		}

		showSnackbar(
			'success',
			__( sprintf( '"%s" has been successfully updated.', 'remote-data-blocks' ), source.slug )
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
			__( sprintf( '"%s" has been successfully added.', 'remote-data-blocks' ), source.slug )
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
			__( sprintf( '"%s" has been successfully deleted.', 'remote-data-blocks' ), source.slug )
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
		dataSources,
		deleteDataSource,
		loadingDataSources,
		updateDataSource,
		fetchDataSources,
		slugConflicts,
		loadingSlugConflicts,
		checkSlugConflict,
	};
};
