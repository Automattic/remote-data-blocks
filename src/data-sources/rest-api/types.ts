import { RestApiConfig, ApiAuthFormState } from '@/data-sources/types';

export type RestApiFormState = Omit< RestApiConfig, 'service' | 'uuid' | 'auth' > &
	ApiAuthFormState;
