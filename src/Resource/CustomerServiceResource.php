<?php

declare(strict_types=1);

namespace PhPicnic\Resource;

use PhPicnic\Action\FetchContactInfo;
use PhPicnic\Action\FetchMessages;
use PhPicnic\Action\FetchParcels;
use PhPicnic\Action\FetchPublicContactInfo;
use PhPicnic\Action\FetchReminders;
use PhPicnic\Action\SaveReminders;
use PhPicnic\Dto\Parcel;
use PhPicnic\LazyLoginApi;

/**
 * Customer service, messages and parcels: `$picnic->customerService()`.
 */
final readonly class CustomerServiceResource
{
    private FetchContactInfo $fetchContactInfo;

    private FetchPublicContactInfo $fetchPublicContactInfo;

    private FetchMessages $fetchMessages;

    private FetchReminders $fetchReminders;

    private SaveReminders $saveReminders;

    private FetchParcels $fetchParcels;

    public function __construct(LazyLoginApi $api)
    {
        $this->fetchContactInfo = new FetchContactInfo($api);
        $this->fetchPublicContactInfo = new FetchPublicContactInfo($api);
        $this->fetchMessages = new FetchMessages($api);
        $this->fetchReminders = new FetchReminders($api);
        $this->saveReminders = new SaveReminders($api);
        $this->fetchParcels = new FetchParcels($api);
    }

    /**
     * @return array<mixed>
     */
    public function fetchContactInfo(): array
    {
        return $this->fetchContactInfo->execute();
    }

    /**
     * @return array<mixed>
     */
    public function fetchPublicContactInfo(): array
    {
        return $this->fetchPublicContactInfo->execute();
    }

    /**
     * @param list<string> $displayPositions
     *
     * @return array<mixed>
     */
    public function fetchMessages(array $displayPositions = []): array
    {
        return $this->fetchMessages->execute($displayPositions);
    }

    /**
     * @return array<mixed>
     */
    public function fetchReminders(): array
    {
        return $this->fetchReminders->execute();
    }

    /**
     * @param list<array{day_of_week: string, time_of_day: array{int, int}}> $reminders
     */
    public function saveReminders(array $reminders): void
    {
        $this->saveReminders->execute($reminders);
    }

    /**
     * @return list<Parcel>
     */
    public function fetchParcels(): array
    {
        return $this->fetchParcels->execute();
    }
}
