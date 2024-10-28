import {
	Button,
	Card,
	CardBody,
	CardFooter,
	CardHeader,
	__experimentalHeading as Heading,
	Popover,
} from '@wordpress/components';
import { WPFormat, useAnchor } from '@wordpress/rich-text';

import { FieldShortcodeSelectField } from '@/blocks/remote-data-container/components/field-shortcode/select-field';

interface FieldShortcodeSelectFieldPopoverProps {
	contentRef: React.RefObject< HTMLElement >;
	fieldSelection: FieldSelection;
	formatTypeSettings: WPFormat;
	onSelectField: ( data: FieldSelection, fieldValue: string ) => void;
	onClose: () => void;
	resetField: ( dataSource: string ) => void;
}

export function FieldShortcodeSelectFieldPopover( props: FieldShortcodeSelectFieldPopoverProps ) {
	const popoverAnchor = useAnchor( {
		editableContentElement: props.contentRef.current,
		settings: props.formatTypeSettings,
	} );
	const { remoteData, selectedField, type } = props.fieldSelection;

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
					<Heading level={ 4 }>Select a field to bind</Heading>
				</CardHeader>
				<CardBody>
					<FieldShortcodeSelectField
						blockName={ remoteData.blockName }
						fieldType={ type ?? 'field' }
						onSelectField={ ( data, fieldValue ) =>
							props.onSelectField( { ...data, action: 'field_updated' }, fieldValue )
						}
						queryInput={ remoteData.queryInput }
						selectedField={ selectedField }
					/>
				</CardBody>
				<CardFooter>
					<Button onClick={ () => props.resetField( remoteData.dataSource ) } isDestructive>
						Reset field
					</Button>
				</CardFooter>
			</Card>
		</Popover>
	);
}
