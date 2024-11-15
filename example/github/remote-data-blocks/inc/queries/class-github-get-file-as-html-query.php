<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\GitHub;

use RemoteDataBlocks\Config\DataSource\HttpDataSource;
use RemoteDataBlocks\Config\QueryContext\HttpQueryContext;
use RemoteDataBlocks\Integrations\GitHub\GitHubDataSource;
use RemoteDataBlocks\Integrations\GitHub\GitHubResponseParser;

class GitHubGetFileAsHtmlQuery extends HttpQueryContext {
	/**
	 * @inheritDoc
	 * @param string|null $default_file_extension Optional file extension to append if missing (e.g., '.md')
	 */
	public function __construct(
		private HttpDataSource $data_source,
		private ?string $default_file_extension = null
	) {
		parent::__construct( $data_source );
	}

	private function ensure_file_extension( string $file_path ): string {
		if ( ! $this->default_file_extension ) {
			return $file_path;
		}

		return str_ends_with( $file_path, $this->default_file_extension ) ? $file_path : $file_path . $this->default_file_extension;
	}

	public function get_input_schema(): array {
		return [
			'file_path' => [
				'name' => 'File Path',
				'type' => 'string',
				'overrides' => [
					[
						'target' => 'utm_content',
						'type' => 'url',
					],
				],
				'generate' => function ( array $data ): string {
					return $this->ensure_file_extension( $data['file_path'] );
				},
			],
		];
	}

	public function get_output_schema(): array {
		return [
			'is_collection' => false,
			'mappings' => [
				'file_content' => [
					'name' => 'File Content',
					'path' => '$.content',
					'type' => 'html',
				],
				'file_path' => [
					'name' => 'File Path',
					'path' => '$.path',
					'type' => 'string',
				],
			],
		];
	}

	public function get_endpoint( array $input_variables ): string {
		/** @var GitHubDataSource $data_source */
		$data_source = $this->get_data_source();

		return sprintf(
			'https://api.github.com/repos/%s/%s/contents/%s?ref=%s',
			$data_source->get_repo_owner(),
			$data_source->get_repo_name(),
			$input_variables['file_path'],
			$data_source->get_ref()
		);
	}

	public function get_request_headers( array $input_variables ): array {
		return [
			'Accept' => 'application/vnd.github.html+json',
		];
	}

	public function process_response( string $html_response_data, array $input_variables ): array {
		$content = $html_response_data;
		if ( '.md' === $this->default_file_extension ) {
			$content = GitHubResponseParser::update_markdown_links( $content );
		}

		return [
			'content' => $content,
			'file_path' => $input_variables['file_path'],
		];
	}
}
