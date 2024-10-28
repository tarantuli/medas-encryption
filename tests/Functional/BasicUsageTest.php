<?php

declare(strict_types=1);

namespace Medas\EncryptionTest\Functional;

use Medas\Encryption\Encryptor;
use PHPUnit\Framework\TestCase;

class BasicUsageTest extends TestCase
{
    public function testEncryption(): void
    {
        $password = random_bytes(20);
        $encryption = service(Encryptor::class);
        $key = sodium_crypto_aead_chacha20poly1305_ietf_keygen();
        $encrypted = $encryption->encrypt($password, $key);

        self::assertEquals($password, $encryption->decrypt($encrypted, $key));

        $encrypted2 = $encryption->encrypt($password, $key);

        self::assertNotEquals($encrypted2, $encrypted);
    }
}
