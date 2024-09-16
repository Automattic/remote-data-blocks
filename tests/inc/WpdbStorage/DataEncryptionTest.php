<?php

namespace RemoteDataBlocks\Tests\WpdbStorage;

use PHPUnit\Framework\TestCase;
use RemoteDataBlocks\WpdbStorage\DataEncryption;

class DataEncryptionTest extends TestCase {
    private $dataEncryption;

    protected function setUp(): void {
        parent::setUp();
        $this->dataEncryption = new DataEncryption();
    }

    public function testEncryptAndDecrypt(): void {
        $originalValue = 'sensitive data';
        $encryptedValue = $this->dataEncryption->encrypt($originalValue);

        $this->assertNotEquals($originalValue, $encryptedValue);
        $this->assertNotFalse($encryptedValue);

        $decryptedValue = $this->dataEncryption->decrypt($encryptedValue);

        $this->assertSame($originalValue, $decryptedValue);
    }

    public function testEncryptWithEmptyString(): void {
        $encryptedValue = $this->dataEncryption->encrypt('');

        $this->assertNotFalse($encryptedValue);
        $this->assertNotEmpty($encryptedValue);

        $decryptedValue = $this->dataEncryption->decrypt($encryptedValue);

        $this->assertSame('', $decryptedValue);
    }

    public function testDecryptWithInvalidInput(): void {
        $invalidInput = 'not_encrypted_data';
        $decryptedValue = $this->dataEncryption->decrypt($invalidInput);

		$this->assertSame( $invalidInput, $decryptedValue );
	}
}