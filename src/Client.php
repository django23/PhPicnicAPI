<?php

declare(strict_types=1);

namespace PhPicnic;

use PhPicnic\Action\FetchLoggedInUser;
use PhPicnic\Dto\User;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\Enum\TwoFactorChannel;
use PhPicnic\Resource\CartResource;
use PhPicnic\Resource\DeliveryResource;
use PhPicnic\Resource\ProductResource;
use PhPicnic\Resource\ShoppingListResource;

/**
 * High-level client for the (unofficial) Picnic API. Account and login methods
 * live here; everything else is grouped into resources: cart(), products(),
 * deliveries() and shoppingLists(). Each resource method delegates to a
 * single-purpose class in {@see Action}.
 *
 * This library is not affiliated with Picnic and talks to the endpoints of the
 * mobile application. Use at your own risk.
 */
final readonly class Client
{
    private LazyLoginApi $api;

    private FetchLoggedInUser $fetchLoggedInUser;

    private CartResource $cartResource;

    private ProductResource $productResource;

    private DeliveryResource $deliveryResource;

    private ShoppingListResource $shoppingListResource;

    public function __construct(
        Credentials $credentials,
        private Session $session,
    ) {
        $this->api = new LazyLoginApi($session, $credentials);
        $this->fetchLoggedInUser = new FetchLoggedInUser($this->api);
        $this->cartResource = new CartResource($this->api);
        $this->productResource = new ProductResource($this->api);
        $this->deliveryResource = new DeliveryResource($this->api);
        $this->shoppingListResource = new ShoppingListResource($this->api);
    }

    /**
     * Build a client, auto-discovering the PSR-18 client and PSR-17 factories
     * when no {@see HttpTransport} is given.
     */
    public static function create(
        Credentials $credentials,
        PicnicConfig $config = new PicnicConfig(),
        ?HttpTransport $transport = null,
    ): self {
        $session = new Session($config, $transport ?? HttpTransport::discover(), $credentials->cachedAuthToken);

        return new self($credentials, $session);
    }

    /**
     * Authenticate explicitly. Called lazily on the first request otherwise.
     *
     * @throws Exception\TwoFactorRequiredException when the account needs 2FA
     */
    public function authenticate(): self
    {
        $this->api->login();

        return $this;
    }

    /**
     * Request a 2FA code be sent. Call this after catching a
     * {@see Exception\TwoFactorRequiredException}, then {@see verifyTwoFactorCode()}.
     */
    public function requestTwoFactorCode(TwoFactorChannel|string $deliveryChannel = TwoFactorChannel::SMS): void
    {
        $channelName = $deliveryChannel instanceof TwoFactorChannel ? $deliveryChannel->value : strtoupper($deliveryChannel);
        $this->session->twoFactor(ApiEndpoint::TWO_FACTOR_GENERATE, ['channel' => $channelName]);
    }

    /**
     * Complete login with the one-time code the user received.
     */
    public function verifyTwoFactorCode(string $oneTimeCode): void
    {
        $this->session->twoFactor(ApiEndpoint::TWO_FACTOR_VERIFY, ['otp' => $oneTimeCode]);
    }

    /**
     * The current (rotating) auth token, so callers can cache it and pass it
     * back through {@see Credentials::$cachedAuthToken} to skip re-authenticating.
     */
    public function currentAuthToken(): ?string
    {
        return $this->session->authToken();
    }

    public function fetchLoggedInUser(): User
    {
        return $this->fetchLoggedInUser->execute();
    }

    public function cart(): CartResource
    {
        return $this->cartResource;
    }

    public function products(): ProductResource
    {
        return $this->productResource;
    }

    public function deliveries(): DeliveryResource
    {
        return $this->deliveryResource;
    }

    public function shoppingLists(): ShoppingListResource
    {
        return $this->shoppingListResource;
    }
}
