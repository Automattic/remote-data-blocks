<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Mocks;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;

class MockLogger implements LoggerInterface {
	private array $logs = [];

	public function log( $level, Stringable|string $message, array $context = [] ): void {
		$this->logs[] = [
			'level' => $level,
			'message' => (string) $message,
			'context' => $context,
		];
	}

	public function emergency( Stringable|string $message, array $context = [] ): void {
		$this->log( LogLevel::EMERGENCY, $message, $context );
	}

	public function alert( Stringable|string $message, array $context = [] ): void {
		$this->log( LogLevel::ALERT, $message, $context );
	}

	public function critical( Stringable|string $message, array $context = [] ): void {
		$this->log( LogLevel::CRITICAL, $message, $context );
	}

	public function error( Stringable|string $message, array $context = [] ): void {
		$this->log( LogLevel::ERROR, $message, $context );
	}

	public function warning( Stringable|string $message, array $context = [] ): void {
		$this->log( LogLevel::WARNING, $message, $context );
	}

	public function notice( Stringable|string $message, array $context = [] ): void {
		$this->log( LogLevel::NOTICE, $message, $context );
	}

	public function info( Stringable|string $message, array $context = [] ): void {
		$this->log( LogLevel::INFO, $message, $context );
	}

	public function debug( Stringable|string $message, array $context = [] ): void {
		$this->log( LogLevel::DEBUG, $message, $context );
	}

	public function getLogs(): array {
		return $this->logs;
	}

	public function clearLogs(): void {
		$this->logs = [];
	}

	public function hasLoggedLevel( string $level ): bool {
		foreach ( $this->logs as $log ) {
			if ( $log['level'] === $level ) {
				return true;
			}
		}
		return false;
	}

	public function getLogsByLevel( string $level ): array {
		return array_filter( $this->logs, fn( $log ) => $log['level'] === $level );
	}
}
