<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Mocks;

class MockWordPressFunctions {
	/** @var array<string, array<mixed>> */
	private static array $done_actions = [];

	/** @var array<string, mixed> */
	private static array $mocked_filters = [];

	/** @var array<string, mixed> */
	private static array $mocked_options = [];

	public static function apply_filters( string $filter, mixed $thing ): mixed {
		return self::$mocked_filters[ $filter ] ?? $thing;
	}

	public static function do_action( string $action, mixed ...$args ): void {
		self::$done_actions[ $action ] = self::$done_actions[ $action ] ?? [];
		self::$done_actions[ $action ][] = $args;
	}

	public static function add_mock_filter( string $filter, mixed $return_value ): void {
		self::$mocked_filters[ $filter ] = $return_value;
	}

	public static function get_done_action( string $action ): array {
		return self::$done_actions[ $action ] ?? [];
	}

	public static function get_option( string $option, mixed $default = false ): mixed {
		return self::$mocked_options[ $option ] ?? $default;
	}

	public static function set_mock_option( string $option, mixed $value ): void {
		self::$mocked_options[ $option ] = $value;
	}

	public static function reset(): void {
		self::$done_actions = [];
		self::$mocked_filters = [];
		self::$mocked_options = [];
	}
}
