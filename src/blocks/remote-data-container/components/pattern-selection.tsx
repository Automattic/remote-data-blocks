import { BlockPattern } from '@wordpress/block-editor';
import { Button, Placeholder } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { blockDefault } from '@wordpress/icons';

import { PatternSelectionModal } from '@/blocks/remote-data-container/components/pattern-selection-modal';

interface PatternSelectionProps {
	insertPatternBlocks: ( pattern: BlockPattern ) => void;
	onCancel: () => void;
	supportedPatterns: BlockPattern[];
}

export function PatternSelection( props: PatternSelectionProps ) {
	const [ showModal, setShowModal ] = useState< boolean >( false );

	function onClickPattern( pattern: BlockPattern ) {
		props.insertPatternBlocks( pattern );
		setShowModal( false );
	}

	function onClose() {
		setShowModal( false );
	}

	if ( showModal ) {
		return (
			<PatternSelectionModal
				supportedPatterns={ props.supportedPatterns }
				onClickPattern={ onClickPattern }
				onClose={ onClose }
			/>
		);
	}

	return (
		<Placeholder icon={ blockDefault } label={ __( 'Choose a pattern to display your data' ) }>
			<Button onClick={ () => setShowModal( true ) } variant="primary">
				{ __( 'Choose a pattern' ) }
			</Button>
			<Button onClick={ props.onCancel } variant="secondary">
				{ __( 'Edit manually' ) }
			</Button>
		</Placeholder>
	);
}
