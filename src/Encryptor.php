<?php

declare(strict_types=1);

namespace Medas\Encryption;

use Medas\Core\Attributes\Service;

#[Service]
readonly class Encryptor
{
    public function __construct(
        private KeyCreator $keyCreator,
    )
    {
    }

    public function encrypt(string $message, string $key): string
    {
        if (strlen($key) !== SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES) {
            throw new Exceptions\WrongKeyLength(
                SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES,
                strlen($key)
            );
        }

        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $cipherText = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($message, '', $nonce, $key);

        return $nonce . $cipherText;
    }

    public function decrypt(string $cipherText, string $key): string
    {
        if (strlen($key) !== SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES) {
            throw new Exceptions\WrongKeyLength(
                SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES,
                strlen($key)
            );
        }

        $nonce = substr($cipherText, 0, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $cipherText = substr($cipherText, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $message = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($cipherText, '', $nonce, $key);

        if ($message === false) {
            throw new Exceptions\DecryptionFailed();
        }

        return $message;
    }

    public function encryptWithPassphrase(string $message, string $passphrase): string
    {
        [$salt, $key] = $this->keyCreator->fromPassphrase($passphrase);

        return $salt . $this->encrypt($message, $key);
    }

    public function decryptWithPassphrase(string $cipherText, string $passphrase): string
    {
        $salt = substr($cipherText, 0, SODIUM_CRYPTO_PWHASH_SALTBYTES);
        [, $key] = $this->keyCreator->fromPassphrase($passphrase, $salt);

        return $this->decrypt(substr($cipherText, SODIUM_CRYPTO_PWHASH_SALTBYTES), $key);
    }
}
