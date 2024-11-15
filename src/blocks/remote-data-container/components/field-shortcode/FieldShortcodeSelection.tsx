import { Spinner } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import { check } from '@wordpress/icons';

import {
	DISPLAY_QUERY_KEY,
	TEXT_FIELD_TYPES,
} from '@/blocks/remote-data-container/config/constants';
import { useRemoteData } from '@/blocks/remote-data-container/hooks/useRemoteData';
import { getBlockAvailableBindings } from '@/utils/localized-block-data';

interface FieldSelectionProps {
	fields: Record< string, { name: string; value: string } >;
	onSelectField: ( data: FieldSelection, fieldValue: string ) => void;
	selectedField?: string;
	remoteData: RemoteData;
	fieldType: 'field' | 'meta';
}

export function FieldSelection( props: FieldSelectionProps ) {
	return (
		<>
			{ Object.entries( props.fields ).map( ( [ fieldName, fieldDetails ], index ) => {
				const fieldSelection: FieldSelection = {
					action: 'add_field_shortcode',
					selectedField: fieldName,
					remoteData: props.remoteData,
					type: props.fieldType,
					selectionPath: 'select_new_tab',
				};

				return (
					<div key={ index } className="remote-data-blocks-inline-field-choice">
						{ fieldDetails.name }:{ ' ' }
						<span
							role="button"
							tabIndex={ 0 }
							className="remote-data-blocks-inline-field-choice-link"
							onClick={ evt => {
								evt.preventDefault();
								props.onSelectField( fieldSelection, fieldDetails.value );
							} }
							onKeyDown={ evt => {
								if ( evt.key.toLowerCase() === 'enter' ) {
									props.onSelectField( fieldSelection, fieldDetails.value );
								}
							} }
						>
							{ fieldDetails.value }
							{ props.selectedField === fieldName && (
								<span className="remote-data-blocks-inline-field-selected-icon">{ check }</span>
							) }
						</span>
					</div>
				);
			} ) }
		</>
	);
}

type FieldSelectionWithFieldsProps = Omit< FieldSelectionProps, 'fields' | 'fieldType' >;

export function FieldSelectionFromAvailableBindings( props: FieldSelectionWithFieldsProps ) {
	const availableBindings = getBlockAvailableBindings( props.remoteData.blockName );

	const fields = Object.entries( availableBindings ).reduce< FieldSelectionProps[ 'fields' ] >(
		( acc, [ fieldName, binding ] ) => {
			const fieldValue = props.remoteData.results[ 0 ]?.[ fieldName ] ?? '';
			if ( ! fieldValue || ! TEXT_FIELD_TYPES.includes( binding.type ) ) {
				return acc;
			}

			return {
				...acc,
				[ fieldName ]: {
					name: binding.name,
					value: fieldValue,
				},
			};
		},
		{}
	);

	return <FieldSelection { ...props } fields={ fields } fieldType="field" />;
}

export function FieldSelectionFromMetaFields( props: FieldSelectionWithFieldsProps ) {
	const fields = Object.entries( props.remoteData.metadata ?? {} ).reduce<
		FieldSelectionProps[ 'fields' ]
	>( ( acc, [ fieldName, metadatum ] ) => {
		return {
			...acc,
			[ fieldName ]: {
				name: metadatum.name,
				value: metadatum.value,
			},
		};
	}, {} );

	return <FieldSelection { ...props } fields={ fields } fieldType="meta" />;
}

interface FieldShortcodeSelectFieldProps {
	blockName: string;
	fieldType: 'field' | 'meta';
	onSelectField: ( data: FieldSelection, fieldValue: string ) => void;
	queryInput: RemoteDataQueryInput;
	selectedField?: string;
}

export function FieldShortcodeSelectField( props: FieldShortcodeSelectFieldProps ) {
	const { data, execute, loading } = useRemoteData( props.blockName, DISPLAY_QUERY_KEY );

	useEffect( () => {
		if ( loading || data ) {
			return;
		}

		void execute( props.queryInput );
	}, [ loading, data ] );

	if ( ! data || loading ) {
		return <Spinner />;
	}

	const selectionProps: FieldSelectionWithFieldsProps = {
		onSelectField: props.onSelectField,
		remoteData: data,
		selectedField: props.selectedField,
	};

	if ( 'meta' === props.fieldType ) {
		return <FieldSelectionFromMetaFields { ...selectionProps } />;
	}

	return <FieldSelectionFromAvailableBindings { ...selectionProps } />;
}
