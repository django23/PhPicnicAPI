<?php

declare(strict_types=1);

namespace PhPicnic\Resource;

use PhPicnic\Action\FetchAllDeliveries;
use PhPicnic\Action\FetchAvailableDeliverySlots;
use PhPicnic\Action\FetchCurrentDeliveries;
use PhPicnic\Action\FetchDeliveryById;
use PhPicnic\Action\FetchDeliveryDriverPosition;
use PhPicnic\Action\FetchDeliveryRoutingScenario;
use PhPicnic\Dto\Delivery;
use PhPicnic\Dto\DeliverySlot;
use PhPicnic\LazyLoginApi;

/**
 * Deliveries and delivery slots: `$picnic->deliveries()`.
 */
final readonly class DeliveryResource
{
    private FetchAvailableDeliverySlots $fetchAvailableDeliverySlots;

    private FetchDeliveryById $fetchDeliveryById;

    private FetchAllDeliveries $fetchAllDeliveries;

    private FetchCurrentDeliveries $fetchCurrentDeliveries;

    private FetchDeliveryRoutingScenario $fetchDeliveryRoutingScenario;

    private FetchDeliveryDriverPosition $fetchDeliveryDriverPosition;

    public function __construct(LazyLoginApi $api)
    {
        $this->fetchAvailableDeliverySlots = new FetchAvailableDeliverySlots($api);
        $this->fetchDeliveryById = new FetchDeliveryById($api);
        $this->fetchAllDeliveries = new FetchAllDeliveries($api);
        $this->fetchCurrentDeliveries = new FetchCurrentDeliveries($api);
        $this->fetchDeliveryRoutingScenario = new FetchDeliveryRoutingScenario($api);
        $this->fetchDeliveryDriverPosition = new FetchDeliveryDriverPosition($api);
    }

    /**
     * @return list<DeliverySlot>
     */
    public function fetchAvailableSlots(): array
    {
        return $this->fetchAvailableDeliverySlots->execute();
    }

    public function fetchById(string $deliveryId): Delivery
    {
        return $this->fetchDeliveryById->execute($deliveryId);
    }

    /**
     * @return list<Delivery>
     */
    public function fetchAll(): array
    {
        return $this->fetchAllDeliveries->execute();
    }

    /**
     * @return list<Delivery>
     */
    public function fetchCurrent(): array
    {
        return $this->fetchCurrentDeliveries->execute();
    }

    /**
     * @return array<mixed>
     */
    public function fetchRoutingScenario(string $deliveryId): array
    {
        return $this->fetchDeliveryRoutingScenario->execute($deliveryId);
    }

    /**
     * @return array<mixed>
     */
    public function fetchDriverPosition(string $deliveryId): array
    {
        return $this->fetchDeliveryDriverPosition->execute($deliveryId);
    }
}
