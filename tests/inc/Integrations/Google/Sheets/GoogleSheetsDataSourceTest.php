<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Integrations\Google\Sheets;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Integrations\Google\Sheets\GoogleSheetsDataSource;

class GoogleSheetsDataSourceTest extends TestCase {
	private GoogleSheetsDataSource $data_source;
	private const MOCK_CREDENTIALS = [
		'type' => 'service_account',
		'project_id' => 'test_project_id',
		'private_key_id' => 'test_private_key_id',
		'private_key' => 'test_private_key',
		'client_email' => 'test@example.com',
		'client_id' => 'test_client_id',
		'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
		'token_uri' => 'https://oauth2.googleapis.com/token',
		'auth_provider_x509_cert_url' => 'test_auth_provider_x509_cert_url',
		'client_id' => 'test_client_id',
		'client_secret' => 'test_client_secret',
		'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
		'client_x509_cert_url' => 'https://www.googleapis.com/robot/v1/metadata/x509/test%40test-project-id.iam.gserviceaccount.com',
		'universe_domain' => 'googleapis.com',
	];

	protected function setUp(): void {
		parent::setUp();

		$this->data_source = GoogleSheetsDataSource::create(
			self::MOCK_CREDENTIALS,
			'test_spreadsheet_id',
			'Test Display Name'
		);
	}

	public function test_get_display_name(): void {
		$this->assertSame(
			'Google Sheets: Test Display Name',
			$this->data_source->get_display_name()
		);
	}

	public function test_get_endpoint(): void {
		$this->assertSame(
			'https://sheets.googleapis.com/v4/spreadsheets/test_spreadsheet_id',
			$this->data_source->get_endpoint()
		);
	}

	public function test_create(): void {
		$data_source = GoogleSheetsDataSource::create(
			self::MOCK_CREDENTIALS,
			'test_spreadsheet_id',
			'New Google Sheet'
		);

		$this->assertInstanceOf( GoogleSheetsDataSource::class, $data_source );
		$this->assertSame( 'Google Sheets: New Google Sheet', $data_source->get_display_name() );
		$this->assertSame( 'https://sheets.googleapis.com/v4/spreadsheets/test_spreadsheet_id', $data_source->get_endpoint() );
	}
}
