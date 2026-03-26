<?php

declare(strict_types=1);

namespace App\Exception;

use Throwable;

class BusinessException extends \Exception
{
    public function __construct(int $code = 0, string $message = '', Throwable $previous = null)
    {
        if (is_null($message)) {
            $message = '';
        }
        parent::__construct($message, $code, $previous);
    }
}