<?php

namespace XbNz\Resolver\Domain\Ip\Services\MtrDotTools;

use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Webmozart\Assert\Assert;
use XbNz\Resolver\Domain\Ip\DTOs\IpData;
use XbNz\Resolver\Domain\Ip\DTOs\MtrDotSh\MtrResultData;
use XbNz\Resolver\Domain\Ip\Services\MtrDotTools\Collections\MtrResultsCollection;
use XbNz\Resolver\Domain\Ip\Services\MtrDotTools\Collections\PingResultsCollection;
use XbNz\Resolver\Domain\Ip\Services\MtrDotTools\Collections\ProbesCollection;
use XbNz\Resolver\Domain\Ip\Services\MtrDotTools\DTOs\MtrDotToolsPingResultsData;
use XbNz\Resolver\Domain\Ip\Services\MtrDotTools\DTOs\MtrDotToolsProbeData;
use XbNz\Resolver\Domain\Ip\Services\MtrDotTools\Exceptions\MtrDotToolsException;
use XbNz\Resolver\Domain\Ip\Services\MtrDotTools\Mappers\ListAllProbesMapper;
use XbNz\Resolver\Domain\Ip\Services\MtrDotTools\Mappers\PerformMtrMapper;
use XbNz\Resolver\Domain\Ip\Services\MtrDotTools\Mappers\PerformPingMapper;
use XbNz\Resolver\Domain\Ip\Services\MtrDotTools\Requests\ListAllProbesRequest;
use XbNz\Resolver\Domain\Ip\Services\MtrDotTools\Requests\PerformMtrRequest;
use XbNz\Resolver\Domain\Ip\Services\MtrDotTools\Requests\PerformPingRequest;
use XbNz\Resolver\Support\DTOs\RequestResponseWrapper;
use XbNz\Resolver\Support\Helpers\Send;

class MtrDotToolsService
{

    public function __construct(
        private readonly Client $client,

        private readonly ListAllProbesRequest $listAllProbesRequest,
        private readonly ListAllProbesMapper $listAllProbesMapper,

        private readonly PerformMtrRequest $performMtrRequest,
        private readonly PerformMtrMapper $performMtrMapper,

        private readonly PerformPingRequest $performPingRequest,
        private readonly PerformPingMapper $performPingMapper,
    ) {
    }

    /**
     * @return ProbesCollection<MtrDotToolsProbeData>
     * @throws MtrDotToolsException
     */
    public function listProbes(?callable $intercept = null): ProbesCollection
    {
        $response = Send::sync(
            $this->client,
            ($this->listAllProbesRequest)()
        );

        if ($intercept !== null) {
            $intercept($response);
        }

        return $this->listAllProbesMapper->map($response);
    }


    /**
     * @param array<IpData> $ipData
     * @param ProbesCollection<MtrDotToolsProbeData> $probes
     * @return MtrResultsCollection<MtrResultData>
     */
    public function mtr(
        array $ipData,
        ProbesCollection $probes = new ProbesCollection()
    ): MtrResultsCollection {

        Assert::allIsInstanceOf($ipData, IpData::class);
        Assert::allIsInstanceOf($probes, MtrDotToolsProbeData::class);

        // TODO: Change config logic and fix this


        $requests = Collection::make($ipData)
            ->map(function (IpData $ipDataObject) use ($probes) {
                return Collection::make($probes)
                    ->map(fn(MtrDotToolsProbeData $probe) => ($this->performMtrRequest)($probe, $ipDataObject));
            })->flatten();

        $responses = Send::async($this->client, $requests->toArray());

        return MtrResultsCollection::make($responses)
            ->map(fn (RequestResponseWrapper $wrapper) => $this->performMtrMapper->map($wrapper, $probes));
    }

    /**
     * @return PingResultsCollection<MtrDotToolsPingResultsData>
     */
    public function ping(
        array $ipData,
        ProbesCollection $probes = new ProbesCollection()
    ): PingResultsCollection {

        Assert::allIsInstanceOf($ipData, IpData::class);
        Assert::allIsInstanceOf($probes, MtrDotToolsProbeData::class);

        $requests = Collection::make($ipData)
            ->map(function (IpData $ipDataObject) use ($probes) {
                return Collection::make($probes)
                    ->map(fn(MtrDotToolsProbeData $probe) => ($this->performPingRequest)($probe, $ipDataObject));
            })->flatten();

        $responses = Send::async($this->client, $requests->toArray());

        return PingResultsCollection::make($responses)
            ->map(fn (RequestResponseWrapper $wrapper) => $this->performPingMapper->map($wrapper, $probes));
    }

}