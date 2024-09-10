import { getRestUrl } from '@/utils/localized-block-data';

export const DISPLAY_QUERY_KEY = '__DISPLAY__';
export const REMOTE_DATA_CONTEXT_KEY = 'remote-data-blocks/remoteData';
export const REMOTE_DATA_REST_API_URL = getRestUrl();

export const IMAGE_FIELD_TYPES = [ 'image_alt', 'image_url' ];
export const TEXT_FIELD_TYPES = [ 'number', 'base64', 'price', 'string' ];
