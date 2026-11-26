import { Button, Modal, __experimentalHStack as HStack } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { ItemList } from '@/blocks/remote-data-container/components/item-list/ItemList';
import { useModalState } from '@/blocks/remote-data-container/hooks/useModalState';
import { useRemoteData } from '@/blocks/remote-data-container/hooks/useRemoteData';
import { sendTracksEvent } from '@/blocks/remote-data-container/utils/tracks';
import { getBlockConfig, getBlockDataSourceType } from '@/utils/localized-block-data';
import { createQueryInputsFromRemoteDataResults } from '@/utils/remote-data';

interface DataViewsModalProps {
	className?: string;
	blockName: string;
	headerImage?: string;
	onSelect?: ( data: RemoteDataQueryInput[] ) => void;
	onSelectField?: ( data: FieldSelection, fieldValue: string ) => void;
	queryKey: string;
	renderTrigger?: ( props: { onClick: () => void } ) => React.ReactNode;
	title?: string;
}

export const DataViewsModal: React.FC< DataViewsModalProps > = props => {
	const { className, blockName, onSelect, renderTrigger, title } = props;
	const { close, isOpen, open } = useModalState();
	const [ selection, setSelection ] = useState< RemoteDataApiResult[] >( [] );
	const blockConfig = getBlockConfig( blockName );
	const supportsItemSelection = Boolean( onSelect );

	const modalClassName = [
		'rdb-dataviews-modal',
		className,
		supportsItemSelection ? 'rdb-dataviews-bulk-actions-modal' : null,
	]
		.filter( Boolean )
		.join( ' ' );

	const triggerElement = renderTrigger ? (
		renderTrigger( { onClick: open } )
	) : (
		<Button variant="primary" onClick={ open } __next40pxDefaultSize>
			{ __( 'Select items' ) }
		</Button>
	);

	return (
		<>
			{ triggerElement }
			{ isOpen && (
				<Modal
					className={ modalClassName }
					isFullScreen
					onRequestClose={ close }
					title={ blockConfig?.settings?.title ?? title }
				>
					<DataViewsModalContent
						{ ...props }
						close={ close }
						selection={ selection }
						setSelection={ setSelection }
					/>
				</Modal>
			) }
		</>
	);
};

type DataViewsModalContentProps = Pick<
	DataViewsModalProps,
	'blockName' | 'onSelect' | 'onSelectField' | 'queryKey'
> & {
	close: () => void;
	selection: RemoteDataApiResult[];
	setSelection: ( selection: RemoteDataApiResult[] ) => void;
};

function getRecordKey( row: RemoteDataApiResult ): string | undefined {
	const ids = Object.keys( row.result )
		.sort()
		.filter( slug => row.result[ slug ]?.type === 'id' );
	const values = ids.map( slug => row.result[ slug ]?.value );
	if (
		! ids.length ||
		values.some(
			value => typeof value !== 'number' && ( typeof value !== 'string' || value === '' )
		)
	) {
		return;
	}
	return JSON.stringify( ids.map( ( slug, index ) => [ slug, String( values[ index ] ) ] ) );
}

function getUniqueRecordUuids( rows: RemoteDataApiResult[] ) {
	const unique = new Map< string, string | null >();
	for ( const row of rows ) {
		const key = getRecordKey( row );
		if ( key !== undefined ) {
			unique.set( key, unique.has( key ) ? null : row.uuid );
		}
	}
	return unique;
}

function DataViewsModalContent( props: DataViewsModalContentProps ) {
	const { blockName, close, onSelect, onSelectField, queryKey, selection, setSelection } = props;
	const { data, loading, ...listProps } = useRemoteData( {
		blockName,
		fetchOnMount: true,
		queryKey,
	} );

	const selectionIds = selection.map( item => item.uuid );
	// A fresh response can give a selected record a new row UUID. Keep the
	// original UI ID when the configured record ID still matches.
	let rows = data?.results;
	if ( rows?.length && selection.length ) {
		const selectedByRecord = getUniqueRecordUuids( selection );
		const currentByRecord = getUniqueRecordUuids( rows );
		rows = rows.map( row => {
			const key = getRecordKey( row );
			const uuid =
				key !== undefined && currentByRecord.get( key ) === row.uuid
					? selectedByRecord.get( key )
					: undefined;
			return uuid ? { ...row, uuid } : row;
		} );
	}
	// Keep selected rows from other pages and replace the selection on this page.
	function setSelectionIds( ids: string[] ): void {
		const pageRows = new Map( rows?.map( row => [ row.uuid, row ] ) );
		const next = new Map(
			selection.filter( row => ! pageRows.has( row.uuid ) ).map( row => [ row.uuid, row ] )
		);
		for ( const id of ids ) {
			const row = pageRows.get( id );
			if ( row ) {
				next.set( id, row );
			}
		}
		setSelection( [ ...next.values() ] );
	}

	function save( results: RemoteDataApiResult[] ): void {
		if ( ! onSelect || ! results.length ) {
			return;
		}

		onSelect( createQueryInputsFromRemoteDataResults( results ) );
		sendTracksEvent( 'add_block', {
			action: 'select_item',
			selected_option: 'search_from_list',
			data_source_type: getBlockDataSourceType( blockName ),
		} );
		close();
	}

	return (
		<>
			<ItemList
				{ ...listProps }
				blockName={ blockName }
				loading={ loading }
				onSelect={ onSelect && save }
				onSelectField={ onSelectField }
				results={ loading ? undefined : rows }
				selectionIds={ selectionIds }
				setSelectionIds={ setSelectionIds }
			/>
			{ onSelect && ! loading && (
				<HStack
					className="rdb-dataviews-bulk-actions-footer__selection-total"
					justify="flex-end"
					spacing={ 3 }
				>
					<Button
						disabled={ selection.length === 0 }
						onClick={ () => setSelection( [] ) }
						variant="tertiary"
						isDestructive
						__next40pxDefaultSize
					>
						{ __( 'Clear selection' ) }
					</Button>
					<Button
						disabled={ selection.length === 0 }
						onClick={ () => save( selection ) }
						variant="primary"
						__next40pxDefaultSize
					>
						{ __( 'Select items' ) }
					</Button>
				</HStack>
			) }
		</>
	);
}
