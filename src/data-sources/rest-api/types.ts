import { AUTH_TYPES, API_KEY_ADD_TO, HTTP_METHODS } from '@/data-sources/constants';
import { RestApiConfig } from '@/data-sources/types';

export type RestApiFormState = Omit< RestApiConfig, 'service' | 'uuid' | 'auth' | 'method' > & {
	method: ( typeof HTTP_METHODS )[ number ];
	authType: ( typeof AUTH_TYPES )[ number ];
	authValue: string;
	authKey: string;
	authAddTo: ( typeof API_KEY_ADD_TO )[ number ];
};
