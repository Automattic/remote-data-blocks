<?php

declare(strict_types = 1);

function apply_filters( string $_filter, mixed $thing ): mixed {
	return $thing;
}

function add_action( string $_filter, mixed ...$_args ): void {}

$GLOBALS['__wordpress_done_actions'] = [];
function do_action( string $action, mixed ...$args ): void {
	$GLOBALS['__wordpress_done_actions'][ $action ]   = $GLOBALS['__wordpress_done_actions'][ $action ] ?? [];
	$GLOBALS['__wordpress_done_actions'][ $action ][] = $args;
}

function esc_html( string $text ): string {
	return $text;
}

function register_block_pattern( string $name, array $options ): void {
	// Do nothing
}

function sanitize_title( string $title ): string {
	return str_replace( ' ', '-', strtolower( $title ) );
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

function is_wp_error( $thing ): bool {
	return $thing instanceof \WP_Error;
}

function wp_parse_url( string $url ): array|false {
    // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
	return parse_url( $url );
}

function wp_json_encode( $data ): string {
    // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
	return json_encode( $data );
}

function wp_cache_get(): bool {
	return false;
}

function wp_cache_set(): bool {
	return true;
}

function update_option( string $option, mixed $value ): bool {
	set_mocked_option( $option, $value );
	return true;
}

function get_option( string $option, mixed $default = false ): mixed {
	if ( isset( $GLOBALS['__mocked_options'][ $option ] ) ) {
		return $GLOBALS['__mocked_options'][ $option ];
	}
	return $default;
}

function set_mocked_option( string $option, mixed $value ): void {
	$GLOBALS['__mocked_options'][ $option ] = $value;
}

function clear_mocked_options(): void {
	$GLOBALS['__mocked_options'] = [];
}

function get_page_by_path( string $path ): string {
	return $path ?? 'fake WP_Post';
}

function add_rewrite_rule( string $regex, string $target, string $position ): void {
	// Do nothing
}

function wp_generate_uuid4() {
	return sprintf(
		'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand
		mt_rand( 0, 0xffff ),
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand
		mt_rand( 0, 0xffff ),
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand
		mt_rand( 0, 0xffff ),
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand
		mt_rand( 0, 0x0fff ) | 0x4000,
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand
		mt_rand( 0, 0x3fff ) | 0x8000,
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand
		mt_rand( 0, 0xffff ),
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand
		mt_rand( 0, 0xffff ),
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand
		mt_rand( 0, 0xffff )
	);
}

function is_email( $email ) {
	return filter_var( $email, FILTER_VALIDATE_EMAIL ) !== false;
}

function wp_is_uuid( $uuid, $version = null ) {
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
	public $errors             = array();
	public $error_data         = array();
	protected $additional_data = array();

	public function __construct( $code = '', $message = '', $data = '' ) {
		if ( empty( $code ) ) {
			return;
		}

		$this->add( $code, $message, $data );
	}

	public function get_error_codes() {
		if ( ! $this->has_errors() ) {
			return array();
		}

		return array_keys( $this->errors );
	}

	public function get_error_code() {
		$codes = $this->get_error_codes();

		if ( empty( $codes ) ) {
			return '';
		}

		return $codes[0];
	}

	public function get_error_messages( $code = '' ) {
		// Return all messages if no code specified.
		if ( empty( $code ) ) {
			$all_messages = array();
			foreach ( (array) $this->errors as $code => $messages ) {
				$all_messages = array_merge( $all_messages, $messages );
			}

			return $all_messages;
		}

		if ( isset( $this->errors[ $code ] ) ) {
			return $this->errors[ $code ];
		} else {
			return array();
		}
	}

	public function get_error_message( $code = '' ) {
		if ( empty( $code ) ) {
			$code = $this->get_error_code();
		}
		$messages = $this->get_error_messages( $code );
		if ( empty( $messages ) ) {
			return '';
		}
		return $messages[0];
	}

	public function get_error_data( $code = '' ) {
		if ( empty( $code ) ) {
			$code = $this->get_error_code();
		}

		if ( isset( $this->error_data[ $code ] ) ) {
			return $this->error_data[ $code ];
		}
	}

	public function has_errors() {
		if ( ! empty( $this->errors ) ) {
			return true;
		}
		return false;
	}

	public function add( $code, $message, $data = '' ) {
		$this->errors[ $code ][] = $message;

		if ( ! empty( $data ) ) {
			$this->add_data( $data, $code );
		}
	}

	public function add_data( $data, $code = '' ) {
		if ( empty( $code ) ) {
			$code = $this->get_error_code();
		}

		if ( isset( $this->error_data[ $code ] ) ) {
			$this->additional_data[ $code ][] = $this->error_data[ $code ];
		}

		$this->error_data[ $code ] = $data;
	}
}
