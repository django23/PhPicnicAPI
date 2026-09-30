<?php

declare(strict_types=1);

namespace PhPicnic;

use PhPicnic\Action\FetchLoggedInUser;
use PhPicnic\Dto\User;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\Enum\TwoFactorChannel;
use PhPicnic\Resource\AccountResource;
use PhPicnic\Resource\CartResource;
use PhPicnic\Resource\CategoryResource;
use PhPicnic\Resource\CheckoutResource;
use PhPicnic\Resource\ConsentResource;
use PhPicnic\Resource\CustomerServiceResource;
use PhPicnic\Resource\DeliveryResource;
use PhPicnic\Resource\PageResource;
use PhPicnic\Resource\PaymentResource;
use PhPicnic\Resource\ProductResource;
use PhPicnic\Resource\RecipeResource;

/**
 * High-level client for the (unofficial) Picnic API. Account and login methods
 * live here; everything else is grouped into resources:
 * cart(), checkout(), products(), categories(), deliveries(), payments(), account(), consents(), customerService(), pages() and recipes().
 * Each resource method delegates to a single-purpose class in {@see Action}.
 *
 * This library is not affiliated with Picnic and talks to the endpoints of the
 * mobile application. Use at your own risk.
 */
final readonly class Client
{
    private LazyLoginApi $api;

    private FetchLoggedInUser $fetchLoggedInUser;

    private CartResource $cartResource;

    private CheckoutResource $checkoutResource;

    private ProductResource $productResource;

    private CategoryResource $categoryResource;

    private DeliveryResource $deliveryResource;

    private PaymentResource $paymentResource;

    private AccountResource $accountResource;

    private ConsentResource $consentResource;

    private CustomerServiceResource $customerServiceResource;

    private PageResource $pageResource;

    private RecipeResource $recipeResource;

    public function __construct(
        Credentials $credentials,
        private Session $session,
    ) {
        $this->api = new LazyLoginApi($session, $credentials);
        $this->fetchLoggedInUser = new FetchLoggedInUser($this->api);
        $this->cartResource = new CartResource($this->api);
        $this->checkoutResource = new CheckoutResource($this->api);
        $this->productResource = new ProductResource($this->api);
        $this->categoryResource = new CategoryResource($this->api);
        $this->deliveryResource = new DeliveryResource($this->api);
        $this->paymentResource = new PaymentResource($this->api);
        $this->accountResource = new AccountResource($this->api);
        $this->consentResource = new ConsentResource($this->api);
        $this->customerServiceResource = new CustomerServiceResource($this->api);
        $this->pageResource = new PageResource($this->api);
        $this->recipeResource = new RecipeResource($this->api);
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
     * The current (rotating) auth token, so callers can cache it. Prefer a
     * persistent {@see Contract\AuthTokenStoreInterface} in the {@see PicnicConfig}.
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

    public function checkout(): CheckoutResource
    {
        return $this->checkoutResource;
    }

    public function products(): ProductResource
    {
        return $this->productResource;
    }

    public function categories(): CategoryResource
    {
        return $this->categoryResource;
    }

    public function deliveries(): DeliveryResource
    {
        return $this->deliveryResource;
    }

    public function payments(): PaymentResource
    {
        return $this->paymentResource;
    }

    public function account(): AccountResource
    {
        return $this->accountResource;
    }

    public function consents(): ConsentResource
    {
        return $this->consentResource;
    }

    public function customerService(): CustomerServiceResource
    {
        return $this->customerServiceResource;
    }

    public function pages(): PageResource
    {
        return $this->pageResource;
    }

    public function recipes(): RecipeResource
    {
        return $this->recipeResource;
    }
}
