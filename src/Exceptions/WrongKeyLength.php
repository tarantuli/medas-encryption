<?php

declare(strict_types=1);

namespace Medas\Encryption\Exceptions;

use Medas\Core\Exceptions\BaseException;

class WrongKeyLength extends BaseException
{
    public function __construct(int $expectedLength, int $receivedLength)
    {
        parent::__construct($expectedLength, $receivedLength);
    }

    public function pattern(): string
    {
        return 'wrong key length, expected %s, got %s';
    }
}
