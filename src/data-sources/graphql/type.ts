import { GraphQLConfig, ApiAuthFormState } from '@/data-sources/types';

export type GraphQLFormState = Omit< GraphQLConfig, 'service' | 'uuid' | 'auth' > &
	ApiAuthFormState;
