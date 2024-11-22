import { AUTH_TYPES, API_KEY_ADD_TO } from '@/data-sources/constants';
import { HttpConfig } from '@/data-sources/types';

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
	addTo: ( typeof API_KEY_ADD_TO )[ number ];
}

export interface HttpNoAuth extends BaseHttpAuth {
	type: 'none';
}

export type HttpAuthFormState = {
	authType: ( typeof AUTH_TYPES )[ number ];
	authValue: string;
	authKey: string;
	authAddTo: ( typeof API_KEY_ADD_TO )[ number ];
};

export type HttpFormState = Omit< HttpConfig, 'service' | 'uuid' | 'auth' > & HttpAuthFormState;
