import { Modal } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import AddDataSourceSelector from './AddDataSourceSelector';
import { DataSourceFormModalProps, DataSourceType, DataSourceConfig } from '@/data-sources/types';
import { HttpSettings } from '@/data-sources/http/HttpSettings';
import { AirtableSettings } from '@/data-sources/airtable/AirtableSettings';
import { GoogleSheetsSettings } from '@/data-sources/google-sheets/GoogleSheetsSettings';
import { ShopifySettings } from '@/data-sources/shopify/ShopifySettings';
import { SalesforceD2CSettings } from '@/data-sources/salesforce-d2c/SalesforceD2CSettings';

import './DataSourceFormModal.scss';

export default function DataSourceFormModal( {
	isOpen,
	onRequestClose,
	onDataSourceCreated,
}: DataSourceFormModalProps ) {
	const [ selectedDataSource, setSelectedDataSource ] = useState< DataSourceType | null >( null );

	const handleDataSourceSelect = ( type: DataSourceType ) => {
		setSelectedDataSource( type );
	};

	const handleDataSourceSuccess = ( dataSource: DataSourceConfig ) => {
		if ( onDataSourceCreated ) {
			onDataSourceCreated( dataSource );
		}
		onRequestClose();
	};

	const handleClose = () => {
		setSelectedDataSource( null );
		onRequestClose();
	};

	const renderSettings = () => {
		if ( ! selectedDataSource ) {
			return (
				<div className="data-source-form-modal__select">
					<h2 className="data-source-form-modal__title">
						{ __( 'Select a data source type', 'remote-data-blocks' ) }
					</h2>
					<AddDataSourceSelector onSelect={ handleDataSourceSelect } />
				</div>
			);
		}

		switch ( selectedDataSource ) {
			case 'generic-http':
				return (
					<HttpSettings
						mode="add"
						onSuccess={ handleDataSourceSuccess }
					/>
				);
			case 'airtable':
				return (
					<AirtableSettings
						mode="add"
						onSuccess={ handleDataSourceSuccess }
					/>
				);
			case 'google-sheets':
				return (
					<GoogleSheetsSettings
						mode="add"
						onSuccess={ handleDataSourceSuccess }
					/>
				);
			case 'shopify':
				return (
					<ShopifySettings
						mode="add"
						onSuccess={ handleDataSourceSuccess }
					/>
				);
			case 'salesforce-d2c':
				return (
					<SalesforceD2CSettings
						mode="add"
						onSuccess={ handleDataSourceSuccess }
					/>
				);
			default:
				return null;
		}
	};

	if ( ! isOpen ) {
		return null;
	}

	return (
		<Modal
			className="data-source-form-modal"
			title={ selectedDataSource
				? __( 'Connect a new data source', 'remote-data-blocks' )
				: __( 'Add a new data source', 'remote-data-blocks' )
			}
			onRequestClose={ handleClose }
			isFullScreen
		>
			{ renderSettings() }
		</Modal>
	);
}