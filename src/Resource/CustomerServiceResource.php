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
use PhPicnic\Dto\Reminder;
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
     * @return array<mixed>
     */
    public function fetchMessages(string ...$displayPositions): array
    {
        return $this->fetchMessages->execute(...$displayPositions);
    }

    /**
     * @return array<mixed>
     */
    public function fetchReminders(): array
    {
        return $this->fetchReminders->execute();
    }

    public function saveReminders(Reminder ...$reminders): void
    {
        $this->saveReminders->execute(...$reminders);
    }

    /**
     * @return list<Parcel>
     */
    public function fetchParcels(): array
    {
        return $this->fetchParcels->execute();
    }
}
