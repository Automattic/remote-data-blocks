<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Config;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Config\BlockAttribute\RemoteDataBlockAttribute;

class SavedRemoteDataCompatibilityTest extends TestCase {
	public function test_saved_query_inputs_and_values_survive_repeated_migration(): void {
		$fixtures = json_decode( file_get_contents( __DIR__ . '/../../fixtures/compatibility/saved-remote-data.json' ), true, 512, JSON_THROW_ON_ERROR );

		foreach ( $fixtures as $fixture ) {
			if ( $fixture['clientOnly'] ?? false ) {
				continue;
			}

			$migrated = RemoteDataBlockAttribute::migrate_config( $fixture['remoteData'] );
			$this->assertIsArray( $migrated );
			$this->assertSame( [ [ 'id' => 'saved-row' ] ], $migrated['queryInputs'], $fixture['name'] );
			$this->assertSame( $fixture['values'], array_map( static fn ( array $field ): mixed => $field['value'], $migrated['results'][0]['result'] ), $fixture['name'] );
			$this->assertSame( $migrated, RemoteDataBlockAttribute::migrate_config( $migrated ) );
		}
	}
}
