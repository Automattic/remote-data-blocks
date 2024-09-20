<?php

namespace RemoteDataBlocks\Integrations\Google\Sheets;

use RemoteDataBlocks\Config\ArraySerializableInterface;
use RemoteDataBlocks\Config\Datasource\DatasourceInterface;
use RemoteDataBlocks\Config\Datasource\HttpDatasource;
use RemoteDataBlocks\Integrations\Google\Auth\GoogleAuth;

class GoogleSheetsDatasource extends HttpDatasource implements ArraySerializableInterface {
    private const SERVICE_SCHEMA = [
        'credentials' => [
            'path'     => '$.credentials',
            'required' => true,
            'type'     => 'array',
            'properties' => [
                'type'                        => ['type' => 'string', 'required' => true],
                'project_id'                  => ['type' => 'string', 'required' => true],
                'private_key_id'              => ['type' => 'string', 'required' => true],
                'private_key'                 => ['type' => 'string', 'required' => true],
                'client_email'                => ['type' => 'string', 'required' => true],
                'client_id'                   => ['type' => 'string', 'required' => true],
                'auth_uri'                    => ['type' => 'string', 'required' => true],
                'token_uri'                   => ['type' => 'string', 'required' => true],
                'auth_provider_x509_cert_url' => ['type' => 'string', 'required' => true],
                'client_x509_cert_url'        => ['type' => 'string', 'required' => true],
                'universe_domain'             => ['type' => 'string', 'required' => true],
            ],
        ],
        'spreadsheet' => [
            'path'     => '$.spreadsheet',
            'required' => true,
            'type'     => 'array',
            'properties' => [
                'id'   => ['type' => 'string', 'required' => true],
                'name' => ['type' => 'string', 'required' => true],
            ],
        ],
        'sheet'       => [
            'path'     => '$.sheet',
            'required' => true,
            'type'     => 'array',
            'properties' => [
                'id'   => ['type' => 'integer', 'required' => true],
                'name' => ['type' => 'string', 'required' => true],
            ],
        ],
    ];

    private $credentials;
    private $spreadsheet;
    private $sheet;

    public function __construct(array $config) {
        parent::__construct($config);
        $this->credentials = $config['credentials'];
        $this->spreadsheet = $config['spreadsheet'];
        $this->sheet = $config['sheet'];
    }

    public function get_display_name(): string {
        return sprintf('Google Sheets: %s - %s', $this->spreadsheet['name'], $this->sheet['name']);
    }

    public function get_endpoint(): string {
        return sprintf('https://sheets.googleapis.com/v4/spreadsheets/%s/values/%s', 
            $this->spreadsheet['id'], 
            urlencode($this->sheet['name'])
        );
    }

    public function get_request_headers(): array {
		$access_token = GoogleAuth::generate_token_from_service_account_key(
			$this->credentials,
			GoogleAuth::GOOGLE_SHEETS_SCOPES
		);

		return [
			'Authorization' => sprintf( 'Bearer %s', $access_token ),
			'Content-Type'  => 'application/json',
		];
    }

    public static function get_config_schema(): array {
        return array_merge(DatasourceInterface::BASE_SCHEMA, self::SERVICE_SCHEMA);
    }
}