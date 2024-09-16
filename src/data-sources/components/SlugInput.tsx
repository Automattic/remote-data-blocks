import { TextControl } from '@wordpress/components';
import { useDebounce } from '@wordpress/compose';
import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import React from 'react';

import { useDataSources } from '@/data-sources/hooks/useDataSources';
import { sanitizeDatasourceSlug } from '@/data-sources/utils';

interface SlugInputProps {
	slug: string;
	onChange: ( slug: string ) => void;
	uuid?: string;
}

export const SlugInput: React.FC< SlugInputProps > = ( { slug, onChange, uuid } ) => {
	const { slugConflicts, checkSlugConflict } = useDataSources( false );

	// eslint-disable-next-line @typescript-eslint/no-misused-promises
	const debouncedCheckSlugConflict = useDebounce( checkSlugConflict, 500 );
	const onSlugChange = ( newSlug: string | undefined ): void => {
		const sanitizedSlug = sanitizeDatasourceSlug( newSlug ?? '' );
		if ( sanitizedSlug !== slug ) {
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
			type="text"
			label={ __( 'Slug', 'remote-data-blocks' ) }
			value={ slug }
			onChange={ onSlugChange }
			help={ __(
				slugConflictMessage || 'A unique identifier for this data source.',
				'remote-data-blocks'
			) }
			required
			__next40pxDefaultSize
		/>
	);
};
