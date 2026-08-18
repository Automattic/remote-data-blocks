import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { ConfigSource } from '@/data-sources/constants';
import { HttpSettings } from '@/data-sources/http/HttpSettings';
import { HttpConfig } from '@/data-sources/types';
import { SettingsContext } from '@/settings/hooks/useSettingsNav';

const mocks = vi.hoisted( () => ( {
	canUseDisplayName: vi.fn( () => true ),
	onSave: vi.fn( async () => undefined ),
} ) );

vi.mock( '@/data-sources/hooks/useDataSources', () => ( {
	useDataSources: () => ( {
		canUseDisplayName: mocks.canUseDisplayName,
		onSave: mocks.onSave,
	} ),
} ) );

describe( 'HttpSettings', () => {
	beforeEach( () => {
		mocks.onSave.mockClear();
		document.body.innerHTML = '<div id="rdb-settings-page-form-save-button"></div>';
	} );

	it( 'saves additional cache key request headers', async () => {
		const user = userEvent.setup();
		const config: HttpConfig = {
			config_source: ConfigSource.STORAGE,
			service: 'generic-http',
			service_config: {
				__version: 1,
				auth: { type: 'none', value: '' },
				cache_key_request_headers: [],
				display_name: 'Custom API',
				enable_blocks: false,
				endpoint: 'https://example.com',
			},
			uuid: '00000000-0000-4000-8000-000000000000',
		};

		render(
			<SettingsContext.Provider
				value={ {
					goToMainScreen: vi.fn(),
					pushState: vi.fn(),
					screen: 'editDataSource',
					service: 'generic-http',
				} }
			>
				<HttpSettings mode="edit" uuid={ config.uuid ?? undefined } config={ config } />
			</SettingsContext.Provider>
		);

		const headerInput = screen.getByRole( 'combobox', {
			name: 'Additional cache key headers',
		} );
		await user.type( headerInput, 'X-Tenant-ID{enter}' );
		await user.click( screen.getByRole( 'button', { name: 'Save' } ) );

		expect( mocks.onSave ).toHaveBeenCalledWith(
			expect.objectContaining( {
				service_config: expect.objectContaining( {
					cache_key_request_headers: [ 'X-Tenant-ID' ],
				} ),
			} ),
			'edit'
		);
	} );
} );
