import { Button, SelectControl, CheckboxControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { useDataSources } from '@/data-sources/hooks/useDataSources';
import { useQueries } from '@/data-sources/hooks/useQueries';
import DataSourceFormModal from './DataSourceFormModal';
import {
	DataSourceConfig,
	QueryDataSourceConfig,
	QuerySettingsComponentProps,
} from '@/data-sources/types';
import { SelectOption } from '@/types/input';

import './QueryForm.scss';

export default function QueryForm<T extends QueryDataSourceConfig>( {
	mode,
	uuid,
	config,
	dataSourceUuid,
	onSave,
	children,
}: QuerySettingsComponentProps<T> & {
	onSave: ( data: T ) => void;
	children?: React.ReactNode;
} ) {
	const [ selectedDataSourceUuid, setSelectedDataSourceUuid ] = useState<string | undefined>(
		dataSourceUuid || config?.data_source_uuid
	);
	const [ enableBlocks, setEnableBlocks ] = useState<boolean>(
		config?.query_config?.enable_blocks ?? true
	);
	const [ queryType, setQueryType ] = useState<string>(
		config?.query_config?.query_type || 'default'
	);
	const [ isModalOpen, setIsModalOpen ] = useState<boolean>( false );
	const [ dataSourceOptions, setDataSourceOptions ] = useState<SelectOption[]>( [] );

	const { loadingDataSources: isLoadingDataSources, dataSources, fetchDataSources } = useDataSources();
	const { createQuery } = useQueries();

	// Fetch data sources on mount
	useEffect( () => {
		fetchDataSources();
	}, [ fetchDataSources ] );

	// Update data source options when data sources change
	useEffect( () => {
		if ( dataSources.length > 0 ) {
			const options: SelectOption[] = [
				{
					label: __( 'Select a data source', 'remote-data-blocks' ),
					value: '',
					disabled: true,
				},
				...dataSources.map( ( ds ) => ( {
					label: ds.service_config.display_name,
					value: ds.uuid as string,
				} ) ),
			];
			setDataSourceOptions( options );
		}
	}, [ dataSources ] );

	const getQueryTypeOptions = (): SelectOption[] => {
		if ( ! selectedDataSourceUuid ) {
			return [];
		}

		const dataSource = dataSources.find( ( ds ) => ds.uuid === selectedDataSourceUuid );
		if ( ! dataSource ) {
			return [];
		}

		switch ( dataSource.service ) {
			case 'shopify':
			case 'salesforce-d2c':
				return [
					{
						label: __( 'Product', 'remote-data-blocks' ),
						value: 'product',
					},
				];
			case 'airtable':
				return [
					{
						label: __( 'Airtable', 'remote-data-blocks' ),
						value: 'airtable',
					},
				];
			case 'google-sheets':
				return [
					{
						label: __( 'Google Sheets', 'remote-data-blocks' ),
						value: 'google-sheets',
					},
				];
			default:
				return [];
		}
	};

	const handleSave = async () => {
		if ( ! selectedDataSourceUuid ) {
			return;
		}

		const dataSource = dataSources.find( ( ds ) => ds.uuid === selectedDataSourceUuid );
		if ( ! dataSource ) {
			return;
		}

		const queryConfig: any = {
			query_type: queryType,
			enable_blocks: enableBlocks,
		};

		// For SalesforceD2C, include store_id if available
		if ( dataSource.service === 'salesforce-d2c' && dataSource.service_config.store_id ) {
			queryConfig.store_id = dataSource.service_config.store_id;
		}

		// For Airtable, include base and tables if available
		if ( dataSource.service === 'airtable' ) {
			if ( dataSource.service_config.base ) {
				queryConfig.base = dataSource.service_config.base;
			}
			if ( dataSource.service_config.tables ) {
				queryConfig.tables = dataSource.service_config.tables;
			}
		}

		// For Google Sheets, include spreadsheet and sheets if available
		if ( dataSource.service === 'google-sheets' ) {
			if ( dataSource.service_config.spreadsheet ) {
				queryConfig.spreadsheet = dataSource.service_config.spreadsheet;
			}
			if ( dataSource.service_config.sheets ) {
				queryConfig.sheets = dataSource.service_config.sheets;
			}
		}

		const data = {
			service: dataSource.service,
			data_source_uuid: selectedDataSourceUuid,
			query_config: queryConfig,
		} as T;

		if ( mode === 'edit' && uuid ) {
			// Update existing query
			onSave( {
				...data,
				uuid: uuid,
			} as T );
		} else {
			// Create new query
			const result = await createQuery( data );
			if ( result ) {
				onSave( result as T );
			}
		}
	};

	const handleOpenModal = () => {
		setIsModalOpen( true );
	};

	const handleCloseModal = () => {
		setIsModalOpen( false );
	};

	const handleDataSourceCreated = ( dataSource: DataSourceConfig ) => {
		// Update data sources list
		fetchDataSources();
		// Select the newly created data source
		setSelectedDataSourceUuid( dataSource.uuid as string );
	};

	return (
		<div className="query-form">
			<div className="query-form__header">
				<h2 className="query-form__title">
					{ mode === 'edit'
						? __( 'Edit Query', 'remote-data-blocks' )
						: __( 'Create Query', 'remote-data-blocks' )
					}
				</h2>
			</div>

			<div className="query-form__body">
				<div className="query-form__field query-form__field--data-source">
					<div className="query-form__data-source-header">
						<label htmlFor="data-source" className="query-form__label">
							{ __( 'Data Source', 'remote-data-blocks' ) }
						</label>
						<Button
							variant="secondary"
							onClick={ handleOpenModal }
							className="query-form__connect-button"
							disabled={ isLoadingDataSources }
						>
							{ __( 'Connect New Data Source', 'remote-data-blocks' ) }
						</Button>
					</div>
					<SelectControl
						id="data-source"
						value={ selectedDataSourceUuid || '' }
						options={ dataSourceOptions }
						onChange={ setSelectedDataSourceUuid }
						disabled={ isLoadingDataSources || mode === 'edit' }
					/>
				</div>

				{ selectedDataSourceUuid && (
					<>
						<div className="query-form__field">
							<label htmlFor="query-type" className="query-form__label">
								{ __( 'Query Type', 'remote-data-blocks' ) }
							</label>
							<SelectControl
								id="query-type"
								value={ queryType }
								options={ getQueryTypeOptions() }
								onChange={ setQueryType }
							/>
						</div>

						<div className="query-form__field">
							<CheckboxControl
								label={ __( 'Enable Blocks', 'remote-data-blocks' ) }
								checked={ enableBlocks }
								onChange={ setEnableBlocks }
								help={ __(
									'Enable blocks for this query in the block editor',
									'remote-data-blocks'
								) }
							/>
						</div>

						{ children }

						<div className="query-form__actions">
							<Button
								variant="primary"
								onClick={ handleSave }
								disabled={ ! selectedDataSourceUuid }
							>
								{ mode === 'edit'
									? __( 'Update Query', 'remote-data-blocks' )
									: __( 'Create Query', 'remote-data-blocks' )
								}
							</Button>
						</div>
					</>
				) }
			</div>

			<DataSourceFormModal
				isOpen={ isModalOpen }
				onRequestClose={ handleCloseModal }
				onDataSourceCreated={ handleDataSourceCreated }
			/>
		</div>
	);
}