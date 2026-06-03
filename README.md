# medas-encryption

Part of the [Medas framework](https://github.com/tarantuli/medas-core).

## Description

Provides symmetric authenticated encryption via libsodium's XChaCha20-Poly1305 IETF construction (`ext-sodium`). A
random 24-byte nonce is generated for every `encrypt()` call and prepended to the ciphertext, so the output of each
call is unique even for identical plaintext and key inputs. `decrypt()` extracts the nonce from the first 24 bytes
before decrypting.

The Poly1305 authentication tag guarantees both confidentiality and integrity — decryption throws `DecryptionFailed` if
the ciphertext has been tampered with, the key is wrong, or the nonce has been corrupted.

**Services:**

| Class        | Purpose                                                                        |
|--------------|--------------------------------------------------------------------------------|
| `Encryptor`  | Encrypts and decrypts strings with a raw key or a passphrase                   |
| `KeyCreator` | Generates random keys and derives keys from passphrases via Argon2id           |
| `Converter`  | Converts between raw binary and base64 using libsodium's constant-time encoder |

Keys must be exactly `SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES` (32) bytes long.

## Usage

### Package developer context

Register the package:

```php
use Medas\Encryption\EncryptionPackage;

EncryptionPackage::instance();
```

**Encrypting and decrypting with a raw key:**

```php
use Medas\Encryption\Encryptor;
use Medas\Core\Attributes\Service;

#[Service]
readonly class SecretStore
{
    public function __construct(
        private Encryptor $encryptor,
    ) {}

    public function store(string $plaintext, string $key): string
    {
        // Returns binary: 24-byte nonce + ciphertext + 16-byte auth tag
        return $this->encryptor->encrypt($plaintext, $key);
    }

    public function retrieve(string $ciphertext, string $key): string
    {
        // Returns the original plaintext or throws DecryptionFailed
        return $this->encryptor->decrypt($ciphertext, $key);
    }
}
```

**Generating a key:**

```php
use Medas\Encryption\KeyCreator;
use Medas\Core\Attributes\Service;

#[Service]
readonly class KeySetup
{
    public function __construct(
        private KeyCreator  $keyCreator,
        private Converter   $converter,
    ) {}

    public function generate(): string
    {
        // Random 32-byte key, safe to use immediately
        $key = $this->keyCreator->create();

        // Encode as base64 for storage in .env or config
        return $this->converter->bin2base64($key);
    }

    public function load(string $base64Key): string
    {
        return $this->converter->base642bin($base64Key);
    }
}
```

**Encrypting and decrypting with a passphrase:**

When you want human-memorable secrets rather than random binary keys, use the passphrase methods. The salt is
automatically generated, prepended to the output, and re-extracted on decryption — no manual salt management needed.

```php
use Medas\Encryption\{Encryptor, Converter};
use Medas\Core\Attributes\Service;

#[Service]
readonly class PassphraseStore
{
    public function __construct(
        private Encryptor $encryptor,
        private Converter $converter,
    ) {}

    public function store(string $plaintext, string $passphrase): string
    {
        // Output: 32-byte salt + 24-byte nonce + ciphertext + 16-byte auth tag
        $binary = $this->encryptor->encryptWithPassphrase($plaintext, $passphrase);

        return $this->converter->bin2base64($binary);
    }

    public function retrieve(string $stored, string $passphrase): string
    {
        $binary = $this->converter->base642bin($stored);

        return $this->encryptor->decryptWithPassphrase($binary, $passphrase);
    }
}
```

Note: `encryptWithPassphrase` runs Argon2id key derivation on every call, which is intentionally slow
(`OPSLIMIT_INTERACTIVE` / `MEMLIMIT_INTERACTIVE`). It is not suitable for encrypting large volumes of data in a tight
loop — derive the key once with `KeyCreator::fromPassphrase()` and reuse it instead.

**Deriving a key from a passphrase manually:**

```php
use Medas\Encryption\{KeyCreator, Encryptor};

// First call: derive a new key with a fresh random salt
[$salt, $key] = $this->keyCreator->fromPassphrase($passphrase);

// Encrypt with the derived key; store $salt alongside the ciphertext
$ciphertext = $this->encryptor->encrypt($plaintext, $key);

// Later: re-derive the same key using the stored salt
[, $key] = $this->keyCreator->fromPassphrase($passphrase, $salt);
$plaintext = $this->encryptor->decrypt($ciphertext, $key);
```

**Storing encrypted values as base64 for text columns:**

```php
use Medas\Encryption\{Encryptor, Converter};

$binary  = $this->encryptor->encrypt($plaintext, $key);
$stored  = $this->converter->bin2base64($binary);   // safe for VARCHAR / JSON

$binary  = $this->converter->base642bin($stored);
$plaintext = $this->encryptor->decrypt($binary, $key);
```

**Handling decryption failure:**

`decrypt()` and `decryptWithPassphrase()` throw `DecryptionFailed` when authentication fails — wrong key, corrupted
ciphertext, or tampered data. `encrypt()` and `decrypt()` throw `WrongKeyLength` if the key is not exactly 32 bytes.
Always catch both at the call site:

```php
use Medas\Encryption\Exceptions\{DecryptionFailed, WrongKeyLength};

try {
    $plaintext = $this->encryptor->decrypt($ciphertext, $key);
} catch (WrongKeyLength $e) {
    // Key was not 32 bytes — likely a loading or encoding error
} catch (DecryptionFailed) {
    // Wrong key, corrupted data, or tampered ciphertext
}
```

`encryptWithPassphrase()` and `decryptWithPassphrase()` never throw `WrongKeyLength` since the key is derived
internally by `KeyCreator::fromPassphrase()` and is always the correct length.

### Backend user context

**Key storage** — never hard-code keys in source code. Generate a key once with `KeyCreator::create()`, encode it with
`Converter::bin2base64()`, and store the result as an environment variable:

```yaml
encryption:
  key: $env(ENCRYPTION_KEY)
```

Then decode it in your bootstrap or service:

```php
$key = $this->converter->base642bin($configManager->getValue('encryption.key'));
```

**Key rotation** — since the nonce is stored with each ciphertext, re-encrypting with a new key requires decrypting
with the old key first. Keep old keys available until all records have been migrated.

**Binary vs text storage** — `encrypt()` returns raw binary. Use `Converter::bin2base64()` before storing in text
columns, and `Converter::base642bin()` before calling `decrypt()`.

**Passphrase strength** — `KeyCreator::fromPassphrase()` uses Argon2id with `OPSLIMIT_INTERACTIVE` and
`MEMLIMIT_INTERACTIVE`, which is appropriate for user-facing passphrases. For server-side secrets where the passphrase
is machine-generated, a random key via `KeyCreator::create()` is both faster and more secure.
