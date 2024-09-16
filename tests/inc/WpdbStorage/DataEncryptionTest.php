<?php

namespace RemoteDataBlocks\Tests\WpdbStorage;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\WpdbStorage\DataEncryption;

class DataEncryptionTest extends TestCase {
	private $data_encryption;

	protected function setUp(): void {
		parent::setUp();
		$this->data_encryption = new DataEncryption();
	}

	public function testEncryptAndDecrypt(): void {
		$original_value  = 'sensitive data';
		$encrypted_value = $this->data_encryption->encrypt( $original_value );

		$this->assertNotEquals( $original_value, $encrypted_value );
		$this->assertNotFalse( $encrypted_value );

		$decrypted_value = $this->data_encryption->decrypt( $encrypted_value );

		$this->assertSame( $original_value, $decrypted_value );
	}

	public function testEncryptWithEmptyString(): void {
		$encrypted_value = $this->data_encryption->encrypt( '' );

		$this->assertNotFalse( $encrypted_value );
		$this->assertNotEmpty( $encrypted_value );

		$decrypted_value = $this->data_encryption->decrypt( $encrypted_value );

		$this->assertSame( '', $decrypted_value );
	}

	public function testDecryptWithInvalidInput(): void {
		$invalid_input   = 'not_encrypted_data';
		$decrypted_value = $this->data_encryption->decrypt( $invalid_input );

		$this->assertSame( $invalid_input, $decrypted_value );
	}
}
