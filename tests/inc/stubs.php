<?php declare(strict_types = 1);

use RemoteDataBlocks\Tests\Mocks\MockWordPressFunctions;

function add_action(): void {}
function add_filter(): void {}

function do_action( string $action, mixed ...$args ): void {
	MockWordPressFunctions::do_action( $action, ...$args );
}

function apply_filters( string $filter, mixed $thing, mixed ...$args ): mixed {
	return MockWordPressFunctions::apply_filters( $filter, $thing, ...$args );
}

function esc_html( string $text ): string {
	return $text;
}

function esc_html__( string $text ): string {
	return apply_filters( 'esc_html__', $text );
}

function register_block_pattern( string $_name, array $_options ): void {
	// Do nothing
}

function is_multisite(): void {
	// Do nothing
}

function plugins_url( string $path ): string {
	return sprintf( 'https://example.com/%s/', $path );
}

function sanitize_title( string $title ): string {
	return str_replace( ' ', '-', strtolower( $title ) );
}

function sanitize_title_with_dashes( string $title ): string {
	return preg_replace( '/[^a-z0-9-]/', '-', sanitize_title( $title ) );
}

function sanitize_text_field( string $text ): string {
	// phpcs:ignore WordPressVIPMinimum.Functions.StripTags.StripTagsOneParameter
	$text = strip_tags( $text );
	$text = trim( $text );
	$text = stripslashes( $text );
	return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
}

function sanitize_email( string $email ): string {
	$email = trim( $email );
	$email = strtolower( $email );
	return filter_var( $email, FILTER_SANITIZE_EMAIL );
}

function sanitize_url( string $url ): string {
	$url = trim( $url );
	$url = filter_var( $url, FILTER_SANITIZE_URL );
	return preg_replace( '/[^-a-zA-Z0-9:_.\/@?&=#%]/', '', $url );
}

function __( string $text ): string {
	return $text;
}

function wp_strip_all_tags( string $string ): string {
	return $string;
}

function is_wp_error( mixed $thing ): bool {
	return $thing instanceof \WP_Error;
}

function wp_parse_url( string $url ): array|false {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
	return parse_url( $url );
}

function wp_json_encode( mixed $data ): string {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
	$string = json_encode( $data );
	return $string ?: '';
}

function wp_cache_get(): bool {
	return false;
}

function wp_cache_set(): bool {
	return true;
}

function update_option( string $option, mixed $value ): bool {
	MockWordPressFunctions::set_mock_option( $option, $value );
	return true;
}

function get_option( string $option, mixed $default = false ): mixed {
	return MockWordPressFunctions::get_option( $option, $default );
}

function get_page_by_path( string $path ): string {
	return $path ?? 'fake WP_Post';
}

function get_query_var( string $var_name, mixed $default_value = null ): ?string {
	return MockWordPressFunctions::get_query_var( $var_name, $default_value );
}

function wp_generate_uuid4(): string {
	return '00000000-0000-4000-8000-000000000000';
}

function is_email( mixed $email ): bool {
	return filter_var( $email, FILTER_VALIDATE_EMAIL ) !== false;
}

function wp_is_uuid( mixed $uuid, ?int $version = null ): bool {
	if ( ! is_string( $uuid ) ) {
		return false;
	}

	if ( is_numeric( $version ) ) {
		if ( 4 !== (int) $version ) {
			throw new Exception( esc_html( 'Only UUID V4 is supported at this time.' ) );
		}
		$regex = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';
	} else {
		$regex = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';
	}

	return (bool) preg_match( $regex, $uuid );
}

class WP_Error {
	public function __construct( private string $code = '', private string $message = '', private mixed $data = null ) {}

	public function get_error_code(): string {
			return $this->code;
	}

	public function get_error_data(): mixed {
		return $this->data;
	}

	public function get_error_message(): string {
		return $this->message;
	}
}
