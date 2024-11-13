import { TextControl } from '@wordpress/components';
import { useDebounce } from '@wordpress/compose';
import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';

import { useDataSources } from '@/data-sources/hooks/useDataSources';
import { sanitizeDataSourceSlug } from '@/data-sources/utils';

interface SlugInputProps {
	slug: string;
	onChange: ( slug: string ) => void;
	uuid?: string;
}

export const SlugInput: React.FC< SlugInputProps > = ( { slug, onChange, uuid } ) => {
	const { slugConflicts, checkSlugConflict } = useDataSources( false );
	const [ newSlug, setNewSlug ] = useState( slug );

	// eslint-disable-next-line @typescript-eslint/no-misused-promises
	const debouncedCheckSlugConflict = useDebounce( checkSlugConflict, 500 );
	const onSlugUpdate = () => {
		const sanitizedSlug = sanitizeDataSourceSlug( newSlug ?? '' );
		if ( sanitizedSlug !== newSlug ) {
			setNewSlug( sanitizedSlug );
			onChange( sanitizedSlug );
			void debouncedCheckSlugConflict( sanitizedSlug, uuid ?? '' );
		}
	};

	const slugConflictMessage = useMemo( () => {
		if ( slugConflicts ) {
			return __( 'Slug is not available. Please choose a different slug.', 'remote-data-blocks' );
		}
		return '';
	}, [ slugConflicts ] );

	return (
		<TextControl
			label={ __( 'Slug', 'remote-data-blocks' ) }
			value={ newSlug }
			onChange={ setNewSlug }
			onBlur={ onSlugUpdate }
			help={ __(
				slugConflictMessage || 'A unique identifier for this data source.',
				'remote-data-blocks'
			) }
			__next40pxDefaultSize
		/>
	);
};
