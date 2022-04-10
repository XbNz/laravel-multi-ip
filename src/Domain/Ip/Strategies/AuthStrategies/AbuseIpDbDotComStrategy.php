<?php

namespace XbNz\Resolver\Domain\Ip\Strategies\AuthStrategies;

use GuzzleHttp\Psr7\Uri;
use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;
use XbNz\Resolver\Support\Actions\GetRandomApiKeyAction;

class AbuseIpDbDotComStrategy implements AuthStrategy
{
    public function __construct(
        private GetRandomApiKeyAction $getRandomApiKey,
    )
    {}

    public function guzzleMiddleware(): callable
    {
        $getRandomApiKey = $this->getRandomApiKey;
        return static function (callable $handler) use ($getRandomApiKey) {
            return static function (RequestInterface $request, array $options) use ($handler, $getRandomApiKey) {
                $uri = $request->getUri();

                $randomKey = $getRandomApiKey->execute($uri->__toString(), 'ip-resolver.api-keys');
                $request = $request->withAddedHeader('key', $randomKey);

                return $handler($request, $options);
            };
        };
    }

    public function supports(string $apiBaseUri): bool
    {
        return Str::of($apiBaseUri)
            ->lower()
            ->contains('abuseipdb.com');
    }
}