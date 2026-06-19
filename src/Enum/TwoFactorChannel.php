<?php

declare(strict_types=1);

namespace PhPicnic\Enum;

/**
 * Channels Picnic can deliver a two-factor authentication code over.
 */
enum TwoFactorChannel: string
{
    case SMS = 'SMS';
    case EMAIL = 'EMAIL';
}
