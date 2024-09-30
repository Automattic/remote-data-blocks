<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Tests\Utils;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\Utils\ArrayUtils;

class ArrayUtilsTest extends TestCase {
	public function test_merge_duplicates_by_key() {
		$array = [
			[
				'id'   => 1,
				'name' => 'John',
			],
			[
				'id'   => 2,
				'name' => 'Jane',
			],
			[
				'id'   => 1,
				'name' => 'John',
			],
		];

		$result = ArrayUtils::merge_duplicates_by_key( $array, 'id' );

		$this->assertCount( 2, $result );
		$this->assertSame( [
			[
				'id'   => 1,
				'name' => 'John',
			],
			[
				'id'   => 2,
				'name' => 'Jane',
			],
		], $result );
	}

	public function test_merge_duplicates_by_key_with_no_duplicates() {
		$array = [
			[
				'id'   => 1,
				'name' => 'John',
			],
			[
				'id'   => 2,
				'name' => 'Jane',
			],
		];

		$result = ArrayUtils::merge_duplicates_by_key( $array, 'id' );

		$this->assertCount( 2, $result );
		$this->assertSame( $array, $result );
	}

	public function test_merge_duplicates_by_key_with_duplicate_inner_array_values() {
		$array = [
			[
				'id'   => 1,
				'name' => 'John',
				'age'  => 20,
			],
			[
				'id'   => 2,
				'name' => 'Jane',
				'age'  => 25,
			],
			[
				'id'   => 1,
				'name' => 'William',
				'age'  => 20,
			],
		];

		$result = ArrayUtils::merge_duplicates_by_key( $array, 'id' );

		$this->assertCount( 2, $result );
		$this->assertSame( [
			[
				'id'   => 1,
				'name' => 'John',
				'age'  => 20,
			],
			[
				'id'   => 2,
				'name' => 'Jane',
				'age'  => 25,
			],
		], $result );
	}
}
