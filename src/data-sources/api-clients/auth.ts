import apiFetch from '@wordpress/api-fetch';

import { REST_BASE_AUTH } from '@/data-sources/constants';
import { GoogleServiceAccountKey } from '@/types/google';

export async function getGoogleAuthTokenFromServiceAccount(
	serviceAccountKey: GoogleServiceAccountKey,
	scopes: string[]
): Promise< string > {
	const requestBody = {
		type: serviceAccountKey.type,
		scopes,
		credentials: serviceAccountKey,
	};

	const response = await apiFetch< { token: string } >( {
		path: `${ REST_BASE_AUTH }/google/token`,
		method: 'POST',
		data: requestBody,
	} );

	return response.token;
}
