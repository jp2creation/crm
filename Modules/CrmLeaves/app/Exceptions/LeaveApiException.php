<?php

namespace Modules\CrmLeaves\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class LeaveApiException extends HttpException
{
    public function __construct(
        string $message,
        int $statusCode,
        public readonly string $errorCode,
    ) {
        parent::__construct($statusCode, $message);
    }
}
