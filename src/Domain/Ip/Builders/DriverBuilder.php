<?php

namespace XbNz\Resolver\Domain\Ip\Builders;

use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Collection;
use Illuminate\Support\ItemNotFoundException;
use XbNz\Resolver\Domain\Ip\Actions\CollectEligibleDriversAction;
use XbNz\Resolver\Domain\Ip\Actions\CreateCollectionFromQueriedIpDataAction;
use XbNz\Resolver\Domain\Ip\Actions\VerifyIpIntegrityAction;
use XbNz\Resolver\Domain\Ip\Collections\IpCollection;
use XbNz\Resolver\Domain\Ip\Drivers\IpApiDotComDriver;
use XbNz\Resolver\Domain\Ip\Drivers\IpDataDotCoDriver;
use XbNz\Resolver\Domain\Ip\Drivers\IpGeolocationDotIoDriver;
use XbNz\Resolver\Domain\Ip\Drivers\IpInfoDotIoDriver;
use XbNz\Resolver\Support\Drivers\Driver;
use XbNz\Resolver\Domain\Ip\DTOs\IpData;
use XbNz\Resolver\Support\Exceptions\DriverNotFoundException;

class DriverBuilder
{
    private Collection $chosenDrivers;
    private IpData $ipData;

    public function __construct(
        private VerifyIpIntegrityAction $verifyIpIntegrity,
        private CreateCollectionFromQueriedIpDataAction $collectionFromQueriedIpDataAction,
    )
    {
        $this->chosenDrivers = collect();
    }

    public function ipInfoDotIo(): static
    {
        $this->chosenDrivers[] = app(IpInfoDotIoDriver::class);
        return $this;
    }

    public function ipGeolocationDotIo(): static
    {
        $this->chosenDrivers[] = app(IpGeolocationDotIoDriver::class);
        return $this;
    }

    public function ipApiDotCom(): static
    {
        $this->chosenDrivers[] = app(IpApiDotComDriver::class);
        return $this;
    }

    public function ipDataDotCo(): static
    {
        $this->chosenDrivers[] = app(IpDataDotCoDriver::class);
        return $this;
    }

    public function normalize(): IpCollection
    {
        $queriedResults = collect();
        $this->chosenDrivers->map(function (Driver $driver) use (&$queriedResults){
            $queriedResults[] = $driver->query($this->ipData);
        });
        return $this->collectionFromQueriedIpDataAction
            ->execute($queriedResults);
    }

    public function raw(): Collection
    {
        $rawResults = collect();
        $this->chosenDrivers->map(function (Driver $driver) use (&$rawResults) {
            $rawResults[] = $driver->raw($this->ipData);
        });
        return $rawResults;
    }

    public function withIp(string $ip): static
    {
        $this->ipData = $this->verifyIpIntegrity->execute($ip);
        return $this;
    }

}