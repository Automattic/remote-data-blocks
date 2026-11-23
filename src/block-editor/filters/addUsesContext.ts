import { BlockConfiguration } from '@wordpress/blocks';

import { REMOTE_DATA_CONTEXT_KEY } from '@/blocks/remote-data-container/config/constants';

/** Add context before first binding without consulting an uninitialized editor store. */
export function addUsesContext(
	settings: BlockConfiguration< RemoteDataInnerBlockAttributes >,
	_name: string
) {
	const { usesContext = [] } = settings;
	if ( usesContext.includes( REMOTE_DATA_CONTEXT_KEY ) ) {
		return settings;
	}
	return { ...settings, usesContext: [ ...usesContext, REMOTE_DATA_CONTEXT_KEY ] };
}
