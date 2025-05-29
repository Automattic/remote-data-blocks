import {
	Button,
	Card,
	CardBody,
	CardFooter,
	CardHeader,
	__experimentalHeading as Heading,
	Popover,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { WPFormat, useAnchor } from '@wordpress/rich-text';

import { InlineBindingSelectField } from '@/block-editor/format-types/inline-binding/components/InlineBindingSelection';
import { getBlockConfig, getDisplayKeyFromQueryKey } from '@/utils/localized-block-data';

interface InlineBindingSelectFieldPopoverProps {
	contentRef: React.RefObject< HTMLElement >;
	fieldSelection: FieldSelection;
	formatTypeSettings: WPFormat;
	onSelectField: ( data: FieldSelection, fieldValue: string ) => void;
	onClose: () => void;
	resetField: ( blockName?: string ) => void;
}

export function InlineBindingSelectFieldPopover( props: InlineBindingSelectFieldPopoverProps ) {
	const popoverAnchor = useAnchor( {
		editableContentElement: props.contentRef.current,
		settings: props.formatTypeSettings,
	} );
	const { remoteData, selectedField, type } = props.fieldSelection;

	// For now, we will use the first compatible selector, but this should be improved.
	// Same as InlineBindingSelection.tsx
	const compatibleSelector = getBlockConfig( remoteData?.blockName ?? '' )?.selectors.find(
		selector => [ 'list', 'search' ].includes( selector.type )
	);

	const queryKey = remoteData?.queryKey ?? compatibleSelector?.query_key ?? '';
	const queryGroup = getDisplayKeyFromQueryKey( remoteData?.blockName ?? '', queryKey );

	return (
		<Popover
			placement="bottom-start"
			anchor={ popoverAnchor }
			className="block-editor-format-toolbar__image-popover"
			noArrow={ false }
			offset={ 8 }
			onClose={ props.onClose }
			// Focus the first element (the field-name combobox) if it's empty when the popover is opened.
			focusOnMount="firstElement"
		>
			<Card style={ { width: '24rem' } }>
				<CardHeader>
					<Heading level={ 4 }>{ __( 'Select a field to bind', 'remote-data-blocks' ) }</Heading>
				</CardHeader>
				<CardBody>
					<InlineBindingSelectField
						blockName={ remoteData?.blockName ?? 'Remote Data Block' }
						queryKey={ queryKey }
						queryGroup={ queryGroup }
						fieldType={ type ?? 'field' }
						onSelectField={ ( data, fieldValue ) =>
							props.onSelectField( { ...data, action: 'update_field_shortcode' }, fieldValue )
						}
						queryInputs={ remoteData?.queryInputs ?? [ {} ] }
						selectedField={ selectedField }
					/>
				</CardBody>
				<CardFooter>
					<Button onClick={ () => props.resetField( remoteData?.blockName ) } isDestructive>
						{ __( 'Reset field', 'remote-data-blocks' ) }
					</Button>
				</CardFooter>
			</Card>
		</Popover>
	);
}
