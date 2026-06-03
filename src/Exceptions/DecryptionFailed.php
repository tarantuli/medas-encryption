<?php

declare(strict_types=1);

namespace Medas\Encryption\Exceptions;

use Medas\Core\Exceptions\BaseException;

class DecryptionFailed extends BaseException
{
    public function pattern(): string
    {
        return 'decryption failed';
    }
}
