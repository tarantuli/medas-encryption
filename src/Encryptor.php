<?php

declare(strict_types=1);

namespace Medas\Encryption;

use Medas\Core\Attributes\Service;

#[Service]
readonly class Encryptor
{
    public function encrypt(string $message, string $key): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $cipherText = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($message, '', $nonce, $key);

        return $nonce . $cipherText;
    }

    public function decrypt(string $cipherText, string $key): string
    {
        $nonce = substr($cipherText, 0, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $cipherText = substr($cipherText, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);

        return sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($cipherText, '', $nonce, $key);
    }
}
