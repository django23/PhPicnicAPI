<?php

declare(strict_types=1);

namespace PhPicnic\Action;

use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\LazyLoginApi;

/**
 * Send a suggestion to Picnic. Untested upstream.
 */
final readonly class SendSuggestion
{
    public function __construct(private LazyLoginApi $api)
    {
    }

    public function execute(string $suggestion): void
    {
        $this->api->post(ApiEndpoint::USER_SUGGESTION->path(), ['suggestion' => $suggestion]);
    }
}
