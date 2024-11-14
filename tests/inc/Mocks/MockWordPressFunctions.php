<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Mocks;

class MockWordPressFunctions {
    private static array $query_vars = [];

    public static function set_query_var( string $key, $value ): void {
        self::$query_vars[$key] = $value;
    }

    public static function get_query_var( string $var, $default = '' ): mixed {
        return self::$query_vars[$var] ?? $default;
    }

    public static function reset(): void {
        self::$query_vars = [];
    }
}
