import { HttpConfig, HttpAuthFormState } from '@/data-sources/types';

export type HttpFormState = Omit< HttpConfig, 'service' | 'uuid' | 'auth' > & HttpAuthFormState;
