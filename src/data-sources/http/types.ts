import { AUTH_TYPES, API_KEY_ADD_TO } from '@/data-sources/constants';

export interface BaseHttpAuth {
	type: ( typeof AUTH_TYPES )[ number ];
	value: string;
}

export interface HttpBearerAuth extends BaseHttpAuth {
	type: 'bearer';
}

export interface HttpBasicAuth extends BaseHttpAuth {
	type: 'basic';
}

export type HttpAuth = HttpBearerAuth | HttpBasicAuth | HttpApiKeyAuth | HttpNoAuth;

export interface HttpApiKeyAuth extends BaseHttpAuth {
	type: 'api-key';
	key: string;
	add_to: ( typeof API_KEY_ADD_TO )[ number ];
}

export interface HttpNoAuth extends BaseHttpAuth {
	type: 'none';
}
