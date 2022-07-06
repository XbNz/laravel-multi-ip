<?php

declare(strict_types=1);

namespace XbNz\Resolver\Support\DTOs;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Webmozart\Assert\Assert;
use XbNz\Resolver\Domain\Ip\Services\Request;
use XbNz\Resolver\Support\Drivers\Driver;

class RequestResponseWrapper
{
    public function __construct(
        public readonly RequestInterface $guzzleRequest,
        public readonly ResponseInterface $guzzleResponse,
    ) {
    }
}
