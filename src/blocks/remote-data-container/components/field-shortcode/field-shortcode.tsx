import { BlockControls } from '@wordpress/block-editor';
import { Modal, ToolbarButton, ToolbarGroup } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { RichTextFormat, insertObject, WPFormat, WPFormatEditProps } from '@wordpress/rich-text';

import { FieldShortcodeSelectField } from '@/blocks/remote-data-container/components/field-shortcode/select-field';
import { FieldShortcodeSelectFieldPopover } from '@/blocks/remote-data-container/components/field-shortcode/select-field-popover';
import { FieldShortcodeSelectTabs } from '@/blocks/remote-data-container/components/field-shortcode/select-tabs';
import { sendTracksEvent } from '@/blocks/remote-data-container/utils/tracks';

const formatName = 'remote-data-blocks/inline-field';

export const formatTypeSettings: WPFormat = {
	attributes: {
		'data-query': 'data-query',
	},
	className: null,
	contentEditable: false,
	edit: FieldShortcodeButton,
	interactive: true,
	name: formatName,
	object: false,
	tagName: 'remote-data-blocks-inline-field',
	title: 'Field Shortcode',
} as WPFormat;

function parseDataQuery( dataQuery?: string ): FieldSelection | null {
	if ( ! dataQuery ) {
		return null;
	}

	try {
		return JSON.parse( dataQuery ) as FieldSelection;
	} catch ( _err ) {
		return null;
	}
}

interface QueryInput {
	blockName: string;
	queryInput: RemoteDataQueryInput;
}

function FieldShortcodeButton( props: WPFormatEditProps ) {
	const { onChange, onFocus, value, isObjectActive, activeObjectAttributes, contentRef } = props;
	const fieldSelection = parseDataQuery( activeObjectAttributes?.[ 'data-query' ] );

	const [ queryInput, setQueryInput ] = useState< QueryInput | null >( null );
	const [ showUI, setShowUI ] = useState< boolean >( false );

	function onClick() {
		setShowUI( ! showUI );
		sendTracksEvent( 'remotedatablocks_field_shortcode', { action: 'toolbar_icon_clicked' } );
	}

	function onClose() {
		setShowUI( false );
		onFocus();
	}

	function onSelectItem( config: BlockConfig, data: RemoteDataQueryInput ) {
		setQueryInput( {
			blockName: config.name,
			queryInput: data,
		} );
	}

	function updateOrInsertField( data: FieldSelection | null, fieldValue: string ) {
		const format: RichTextFormat = {
			attributes: {
				...activeObjectAttributes,
				'data-query': data ? JSON.stringify( data ) : '',
			},
			innerHTML: fieldValue,
			type: formatName,
		};

		if ( Object.keys( activeObjectAttributes ).length ) {
			const replacements = value.replacements.slice();
			replacements[ value.start ] = format;

			onChange( { ...value, replacements } );
			return;
		}

		onChange( insertObject( value, format ) );
	}

	function onSelectField( data: FieldSelection, fieldValue: string ) {
		updateOrInsertField( data, fieldValue );
		onClose();

		sendTracksEvent( 'remotedatablocks_field_shortcode', {
			action: data.action,
			data_source: data.remoteData.dataSource,
			selection_path: data.selectionPath,
		} );
	}

	function resetField( dataSource: string ): void {
		updateOrInsertField( null, 'Unbound field' );
		setQueryInput( null );

		sendTracksEvent( 'remotedatablocks_field_shortcode', {
			action: 'field_reset',
			data_source: dataSource,
		} );
	}

	useEffect( () => {
		if ( isObjectActive ) {
			setShowUI( true );
		}
	}, [ isObjectActive ] );

	return (
		<>
			<BlockControls>
				<ToolbarGroup>
					<ToolbarButton
						icon="shortcode"
						isActive={ isObjectActive }
						onClick={ onClick }
						title="Field shortcode"
					/>
				</ToolbarGroup>
			</BlockControls>

			{ showUI && ! fieldSelection && (
				<Modal
					overlayClassName="remote-data-blocks-pattern__selection-modal"
					title={ __( 'Field shortcode' ) }
					onRequestClose={ onClose }
					isFullScreen
				>
					{ ! queryInput && (
						<FieldShortcodeSelectTabs
							onSelectField={ onSelectField }
							onSelectItem={ onSelectItem }
						/>
					) }
					{ queryInput && (
						<FieldShortcodeSelectField
							blockName={ queryInput.blockName }
							onSelectField={ ( data, fieldValue ) =>
								onSelectField( { ...data, selectionPath: 'select_new_tab' }, fieldValue )
							}
							queryInput={ queryInput.queryInput }
							fieldType="field"
						/>
					) }
				</Modal>
			) }
			{ showUI && fieldSelection && (
				<FieldShortcodeSelectFieldPopover
					contentRef={ contentRef }
					fieldSelection={ fieldSelection }
					formatTypeSettings={ formatTypeSettings }
					onClose={ onClose }
					onSelectField={ ( data, fieldValue ) =>
						onSelectField( { ...data, selectionPath: 'popover' }, fieldValue )
					}
					resetField={ resetField }
				/>
			) }
		</>
	);
}
