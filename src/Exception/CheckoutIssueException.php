<?php

declare(strict_types=1);

namespace PhPicnic\Exception;

/**
 * Thrown when Picnic refuses to start a checkout (error code CART_HAS_ISSUES),
 * for example because an alcohol age check is needed. A non-blocking issue can
 * be resolved by starting the checkout again with {@see $resolveKey}.
 */
final class CheckoutIssueException extends AbstractPicnicException
{
    public const string AGE_VERIFICATION_TYPE = 'LEGACY_ALCOHOL_AGE_VERIFICATION_REQUIRED';

    /**
     * @param array<mixed> $details the "details" object of the error body
     */
    public function __construct(
        string $message,
        public readonly ?string $issueType,
        public readonly bool $isBlocking,
        public readonly ?string $resolveKey,
        public readonly array $details = [],
    ) {
        parent::__construct($message);
    }

    public function isAgeVerificationRequired(): bool
    {
        return $this->issueType === self::AGE_VERIFICATION_TYPE;
    }
}
