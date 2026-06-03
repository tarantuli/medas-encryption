<?php

declare(strict_types=1);

namespace Medas\Encryption;

use Medas\Core\Attributes\Service;

#[Service]
readonly class KeyCreator
{
    public function create(): string
    {
        return sodium_crypto_aead_xchacha20poly1305_ietf_keygen();
    }

    public function fromPassphrase(string $passphrase, string|null $salt = null): array
    {
        $salt ??= random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);

        return [$salt, sodium_crypto_pwhash(
            SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES,
            $passphrase,
            $salt,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE,
        )];
    }
}
