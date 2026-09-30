<?php

declare(strict_types=1);

namespace PhPicnic\Enum;

/**
 * Picnic app versions this client can present itself as (the x-picnic-agent
 * header). Picnic serves older agents the older response format, so the choice
 * matters: from V1_246_1 on, a few pages come back as React Server Components
 * instead of JSON. Never switched automatically; the caller picks.
 *
 * To add a profile: read versionName and versionCode from the decompiled app's
 * AndroidManifest, confirm the "pc:clid" claim in a fresh login token, then run
 * `composer smoke` for that profile.
 */
enum AppProfile: string
{
    /** Legacy baseline. JSON everywhere, including the three pages newer versions serve as RSC. */
    case V1_206_1 = '30100;1.206.1-#15408';

    case V1_236_1 = '30100;1.236.1-15553;';

    /** Newest known and the default. Serves category-tree-root, profile-root and promo-group-deep-dive as RSC. */
    case V1_246_1 = '30100;1.246.1-15599;';

    public function agentString(): string
    {
        return $this->value;
    }
}
