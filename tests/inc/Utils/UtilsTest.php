<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Utils;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Utils\Utils;

class UtilsTest extends TestCase {
	public function test_remove_duplicates_by_key() {
		$array = [
			[ 'id' => 1, 'name' => 'John' ],
			[ 'id' => 2, 'name' => 'Jane' ],
			[ 'id' => 1, 'name' => 'John' ],
		];

		$result = Utils::remove_duplicates_by_key( $array, 'id' );

		$this->assertCount( 2, $result );
		$this->assertSame( [
			[ 'id' => 1, 'name' => 'John' ],
			[ 'id' => 2, 'name' => 'Jane' ],
		], $result );
	}

	public function test_remove_duplicates_by_key_with_no_duplicates() {
		$array = [
			[ 'id' => 1, 'name' => 'John' ],
			[ 'id' => 2, 'name' => 'Jane' ],
		];

		$result = Utils::remove_duplicates_by_key( $array, 'id' );

		$this->assertCount( 2, $result );
		$this->assertSame( $array, $result );
	}
}
