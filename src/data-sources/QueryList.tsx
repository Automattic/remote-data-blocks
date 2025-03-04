import {
	Button,
	Card,
	CardBody,
	CardFooter,
	CardHeader,
	Spinner,
	Modal,
} from '@wordpress/components';
import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useQueries } from './hooks/useQueries';
import { useDataSources } from './hooks/useDataSources';
import QueryForm from './components/QueryForm';
import { QueryDataSourceConfig } from './types';

import './QueryList.scss';

export default function QueryList() {
	const [ selectedQuery, setSelectedQuery ] = useState<QueryDataSourceConfig | null>( null );
	const [ isModalOpen, setIsModalOpen ] = useState<boolean>( false );
	const [ isCreating, setIsCreating ] = useState<boolean>( false );

	const {
		isLoading,
		error,
		queries,
		fetchQueries,
		deleteQuery,
	} = useQueries();

	const { dataSources, fetchDataSources } = useDataSources();

	// Add a ref to track if we've already loaded the data
	const hasLoadedRef = useRef(false);
	
	useEffect( () => {
		// Only fetch data once when component mounts
		if (!hasLoadedRef.current) {
			fetchQueries();
			fetchDataSources();
			hasLoadedRef.current = true;
		}
		
		// Check if we should open the add/edit modal based on URL
		const urlParams = new URLSearchParams( window.location.search );
		const addQuery = urlParams.get('addQuery');
		const editQueryUUID = urlParams.get('editQuery');
		
		if (addQuery) {
			setIsCreating(true);
			setIsModalOpen(true);
		} else if (editQueryUUID && queries.length > 0) {
			const query = queries.find(q => q.uuid === editQueryUUID);
			if (query) {
				setSelectedQuery(query);
				setIsCreating(false);
				setIsModalOpen(true);
			}
		}
	}, [fetchQueries, fetchDataSources, queries] );

	const handleCreateClick = () => {
		// Check if we're using URL-based routing
		const urlParams = new URLSearchParams( window.location.search );
		if (urlParams.get('addQuery')) {
			// URL-based routing already set up
			setIsCreating( true );
			setIsModalOpen( true );
		} else {
			// Use modal without URL
			setIsCreating( true );
			setIsModalOpen( true );
		}
	};

	const handleEditClick = ( query: QueryDataSourceConfig ) => {
		// Check if we're using URL-based routing
		const urlParams = new URLSearchParams( window.location.search );
		if (urlParams.get('editQuery')) {
			// URL-based routing already set up
			setSelectedQuery( query );
			setIsCreating( false );
			setIsModalOpen( true );
		} else {
			// Use modal without URL
			setSelectedQuery( query );
			setIsCreating( false );
			setIsModalOpen( true );
		}
	};

	const handleDeleteClick = async ( uuid: string ) => {
		if ( window.confirm( __( 'Are you sure you want to delete this query?', 'remote-data-blocks' ) ) ) {
			const result = await deleteQuery( uuid );
			if (result) {
				// Only fetch queries once after successful deletion
				fetchQueries();
			}
		}
	};

	const handleCloseModal = () => {
		setIsModalOpen( false );
		setSelectedQuery( null );
		
		// Check if we're using URL-based routing, go back to main screen if needed
		const urlParams = new URLSearchParams( window.location.search );
		if (urlParams.get('addQuery') || urlParams.get('editQuery')) {
			// Remove the query parameter but keep the tab parameter
			const newUrl = new URL(window.location.href);
			newUrl.searchParams.delete('addQuery');
			newUrl.searchParams.delete('editQuery');
			if (window.history && window.history.pushState) {
				window.history.pushState({}, '', newUrl);
			}
		}
	};

	const handleSaveQuery = () => {
		fetchQueries();
		setIsModalOpen( false );
		setSelectedQuery( null );
	};

	const getDataSourceName = ( uuid: string ) => {
		const dataSource = dataSources.find( ( ds ) => ds.uuid === uuid );
		return dataSource ? dataSource.service_config.display_name : uuid;
	};

	if ( isLoading && queries.length === 0 ) {
		return (
			<div className="query-list__loading">
				<Spinner />
				<p>{ __( 'Loading queries...', 'remote-data-blocks' ) }</p>
			</div>
		);
	}

	if ( error ) {
		return (
			<div className="query-list__error">
				<p>{ __( 'Error loading queries:', 'remote-data-blocks' ) } { error.message }</p>
				<Button variant="secondary" onClick={ () => fetchQueries() }>
					{ __( 'Retry', 'remote-data-blocks' ) }
				</Button>
			</div>
		);
	}

	return (
		<div className="query-list">
			<div className="query-list__header">
				<h2 className="query-list__title">{ __( 'Queries', 'remote-data-blocks' ) }</h2>
			</div>

			{ queries.length === 0 ? (
				<div className="query-list__empty">
					<p>{ __( 'No queries found.', 'remote-data-blocks' ) }</p>
					<Button variant="secondary" onClick={ handleCreateClick }>
						{ __( 'Create your first query', 'remote-data-blocks' ) }
					</Button>
				</div>
			) : (
				<div className="query-list__grid">
					{ queries.map( ( query ) => (
						<Card key={ query.uuid } className="query-list__card">
							<CardHeader>
								<h3 className="query-list__card-title">
									{ query.query_config.query_type }
								</h3>
								<div className="query-list__card-meta">
									<span className="query-list__card-service">
										{ query.service }
									</span>
								</div>
							</CardHeader>
							<CardBody>
								<div className="query-list__card-info">
									<p>
										<strong>{ __( 'Data Source:', 'remote-data-blocks' ) }</strong>{' '}
										{ getDataSourceName( query.data_source_uuid ) }
									</p>
									<p>
										<strong>{ __( 'Blocks Enabled:', 'remote-data-blocks' ) }</strong>{' '}
										{ query.query_config.enable_blocks
											? __( 'Yes', 'remote-data-blocks' )
											: __( 'No', 'remote-data-blocks' )
										}
									</p>
								</div>
							</CardBody>
							<CardFooter>
								<Button
									variant="secondary"
									onClick={ () => handleEditClick( query ) }
								>
									{ __( 'Edit', 'remote-data-blocks' ) }
								</Button>
								<Button
									variant="tertiary"
									isDestructive
									onClick={ () => handleDeleteClick( query.uuid as string ) }
								>
									{ __( 'Delete', 'remote-data-blocks' ) }
								</Button>
							</CardFooter>
						</Card>
					) ) }
				</div>
			) }

			{ isModalOpen && (
				<Modal
					title={ isCreating
						? __( 'Create Query', 'remote-data-blocks' )
						: __( 'Edit Query', 'remote-data-blocks' )
					}
					onRequestClose={ handleCloseModal }
					isFullScreen
				>
					<QueryForm
						mode={ isCreating ? 'add' : 'edit' }
						uuid={ selectedQuery?.uuid || undefined }
						config={ selectedQuery || undefined }
						onSave={ handleSaveQuery }
					/>
				</Modal>
			) }
		</div>
	);
}