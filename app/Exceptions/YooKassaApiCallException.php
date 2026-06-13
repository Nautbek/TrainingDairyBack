<?php

namespace App\Exceptions;

use RuntimeException;
use YooKassa\Common\Exceptions\ApiException;

class YooKassaApiCallException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $requestContext
     */
    public function __construct(
        public readonly array $requestContext,
        ApiException $apiException,
    ) {
        parent::__construct($apiException->getMessage(), $apiException->getCode(), $apiException);
    }

    public function getApiException(): ApiException
    {
        /** @var ApiException $previous */
        $previous = $this->getPrevious();

        return $previous;
    }
}
