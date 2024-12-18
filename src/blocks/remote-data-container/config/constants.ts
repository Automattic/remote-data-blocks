import { getRestUrl } from '@/utils/localized-block-data';
import { getClassName } from '@/utils/string';

export const SUPPORTED_CORE_BLOCKS = [
	'core/button',
	'core/heading',
	'core/image',
	'core/paragraph',
];

export const DISPLAY_QUERY_KEY = 'display';
export const REMOTE_DATA_CONTEXT_KEY = 'remote-data-blocks/remoteData';
export const REMOTE_DATA_REST_API_URL = getRestUrl();

export const CONTAINER_CLASS_NAME = getClassName( 'container' );

export const IMAGE_FIELD_TYPES = [ 'image_alt', 'image_url' ];
export const TEXT_FIELD_TYPES = [ 'email_address', 'html', 'integer', 'number', 'string' ];
export const BUTTON_FIELD_TYPES = [ 'button_url' ];
