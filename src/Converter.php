<?php

declare(strict_types=1);

namespace Medas\Encryption;

use Medas\Core\Attributes\Service;

#[Service]
readonly class Converter
{
    public function bin2base64(string $bin): string
    {
        return sodium_bin2base64($bin, SODIUM_BASE64_VARIANT_ORIGINAL);
    }

    public function base642bin(string $string): string
    {
        return sodium_base642bin($string, SODIUM_BASE64_VARIANT_ORIGINAL);
    }
}
