import { useCallback, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

import { QueryDataSourceConfig, DataSourceConfig } from '@/data-sources/types';
import { ConfigSource } from '@/data-sources/constants';

interface UseQueriesProps {
	onSuccess?: () => void;
	onError?: ( error: Error ) => void;
}

export function useQueries( props?: UseQueriesProps ) {
	const [ isLoading, setIsLoading ] = useState( false );
	const [ error, setError ] = useState< Error | null >( null );
	const [ queries, setQueries ] = useState< QueryDataSourceConfig[] >( [] );

	const fetchQueries = useCallback( async () => {
		setIsLoading( true );
		setError( null );

		try {
			const response = await apiFetch( {
				path: '/remote-data-blocks/v1/queries',
				method: 'GET',
			} );

			setQueries( response as QueryDataSourceConfig[] );
		} catch ( err ) {
			const error = err instanceof Error ? err : new Error( String( err ) );
			setError( error );
			props?.onError?.( error );
		} finally {
			setIsLoading( false );
		}
	}, [] );

	const fetchQueriesByDataSource = useCallback( async ( dataSourceUuid: string ) => {
		setIsLoading( true );
		setError( null );

		try {
			const response = await apiFetch( {
				path: `/remote-data-blocks/v1/queries/data-source/${ dataSourceUuid }`,
				method: 'GET',
			} );

			setQueries( response as QueryDataSourceConfig[] );
			return response as QueryDataSourceConfig[];
		} catch ( err ) {
			const error = err instanceof Error ? err : new Error( String( err ) );
			setError( error );
			props?.onError?.( error );
			return [];
		} finally {
			setIsLoading( false );
		}
	}, [ props ] );

	const fetchQuery = useCallback( async ( uuid: string ) => {
		setIsLoading( true );
		setError( null );

		try {
			const response = await apiFetch( {
				path: `/remote-data-blocks/v1/queries/${ uuid }`,
				method: 'GET',
			} );

			return response as QueryDataSourceConfig;
		} catch ( err ) {
			const error = err instanceof Error ? err : new Error( String( err ) );
			setError( error );
			props?.onError?.( error );
			return null;
		} finally {
			setIsLoading( false );
		}
	}, [ props ] );

	const createQuery = useCallback( async (
		data: Omit< QueryDataSourceConfig, 'uuid' | 'config_source' >
	) => {
		setIsLoading( true );
		setError( null );

		try {
			const payload = {
				...data,
				config_source: ConfigSource.STORAGE,
			};

			const response = await apiFetch( {
				path: '/remote-data-blocks/v1/queries',
				method: 'POST',
				data: payload,
			} );

			props?.onSuccess?.();
			return response as QueryDataSourceConfig;
		} catch ( err ) {
			const error = err instanceof Error ? err : new Error( String( err ) );
			setError( error );
			props?.onError?.( error );
			return null;
		} finally {
			setIsLoading( false );
		}
	}, [ props ] );

	const updateQuery = useCallback( async (
		uuid: string,
		data: Partial< QueryDataSourceConfig >
	) => {
		setIsLoading( true );
		setError( null );

		try {
			const response = await apiFetch( {
				path: `/remote-data-blocks/v1/queries/${ uuid }`,
				method: 'PUT',
				data,
			} );

			props?.onSuccess?.();
			return response as QueryDataSourceConfig;
		} catch ( err ) {
			const error = err instanceof Error ? err : new Error( String( err ) );
			setError( error );
			props?.onError?.( error );
			return null;
		} finally {
			setIsLoading( false );
		}
	}, [ props ] );

	const deleteQuery = useCallback( async ( uuid: string ) => {
		setIsLoading( true );
		setError( null );

		try {
			const response = await apiFetch( {
				path: `/remote-data-blocks/v1/queries/${ uuid }`,
				method: 'DELETE',
			} );

			props?.onSuccess?.();
			return response;
		} catch ( err ) {
			const error = err instanceof Error ? err : new Error( String( err ) );
			setError( error );
			props?.onError?.( error );
			return null;
		} finally {
			setIsLoading( false );
		}
	}, [ props ] );

	return {
		isLoading,
		error,
		queries,
		fetchQueries,
		fetchQueriesByDataSource,
		fetchQuery,
		createQuery,
		updateQuery,
		deleteQuery,
	};
}