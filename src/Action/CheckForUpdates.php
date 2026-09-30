<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Dto\UpdateCheckResult;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Ask Picnic whether the app version in use is still supported. The request body echoes the client identity.
 */
final readonly class CheckForUpdates
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    public function execute(): UpdateCheckResult
    {
        $identity = $this->api->identity();
        [$clientId, $versionAndBuild] = array_pad(explode(';', $identity->picnicAgent), 2, '');
        [$version, $buildNumber] = array_pad(explode('-', $versionAndBuild, 2), 2, '');

        return UpdateCheckResult::fromArray($this->api->post(ApiEndpoint::UPDATE_CHECK->path(), [
            'device_id' => $identity->picnicDeviceId,
            'device_name' => 'notAvailable',
            'client_id' => $clientId,
            'version' => $version,
            'device_os' => $identity->picnicAgent,
            'build_number' => ltrim($buildNumber, '#'),
        ]));
    }
}
