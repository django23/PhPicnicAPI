<?php

declare(strict_types=1);

namespace PhPicnic;

use InvalidArgumentException;
use PhPicnic\Action\AddProductsToCart;
use PhPicnic\Action\AddProductToCart;
use PhPicnic\Action\EmptyShoppingCart;
use PhPicnic\Action\FetchAllDeliveries;
use PhPicnic\Action\FetchAllShoppingLists;
use PhPicnic\Action\FetchAvailableDeliverySlots;
use PhPicnic\Action\FetchCurrentDeliveries;
use PhPicnic\Action\FetchDeliveryById;
use PhPicnic\Action\FetchDeliveryDriverPosition;
use PhPicnic\Action\FetchDeliveryRoutingScenario;
use PhPicnic\Action\FetchLoggedInUser;
use PhPicnic\Action\FetchShoppingCart;
use PhPicnic\Action\FetchShoppingListById;
use PhPicnic\Action\FetchShoppingListSublist;
use PhPicnic\Action\RemoveProductFromCart;
use PhPicnic\Action\SearchProducts;
use PhPicnic\Action\SearchProductsRawResponse;
use PhPicnic\Action\SelectDeliverySlotForCart;
use PhPicnic\Dto\Cart;
use PhPicnic\Dto\Delivery;
use PhPicnic\Dto\DeliverySlot;
use PhPicnic\Dto\Product;
use PhPicnic\Dto\User;
use PhPicnic\Enum\CountryCode;
use PhPicnic\Enum\TwoFactorChannel;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * High-level client for the (unofficial) Picnic API. One method per endpoint,
 * each delegating to a single-purpose class in {@see Action}.
 *
 * This library is not affiliated with Picnic and talks to the endpoints of the
 * mobile application. Use at your own risk.
 */
final readonly class Client
{
    private AuthenticatedApi $authenticatedApi;

    private FetchLoggedInUser $fetchLoggedInUser;

    private SearchProducts $searchProducts;

    private SearchProductsRawResponse $searchProductsRawResponse;

    private FetchShoppingCart $fetchShoppingCart;

    private AddProductToCart $addProductToCart;

    private AddProductsToCart $addProductsToCart;

    private RemoveProductFromCart $removeProductFromCart;

    private EmptyShoppingCart $emptyShoppingCart;

    private SelectDeliverySlotForCart $selectDeliverySlotForCart;

    private FetchAvailableDeliverySlots $fetchAvailableDeliverySlots;

    private FetchAllShoppingLists $fetchAllShoppingLists;

    private FetchShoppingListById $fetchShoppingListById;

    private FetchShoppingListSublist $fetchShoppingListSublist;

    private FetchDeliveryById $fetchDeliveryById;

    private FetchDeliveryRoutingScenario $fetchDeliveryRoutingScenario;

    private FetchDeliveryDriverPosition $fetchDeliveryDriverPosition;

    private FetchAllDeliveries $fetchAllDeliveries;

    private FetchCurrentDeliveries $fetchCurrentDeliveries;

    public function __construct(
        string $username,
        string $password,
        private Session $session,
    ) {
        $this->authenticatedApi = new AuthenticatedApi($session, $username, $password);
        $this->fetchLoggedInUser = new FetchLoggedInUser($this->authenticatedApi);
        $this->searchProducts = new SearchProducts($this->authenticatedApi);
        $this->searchProductsRawResponse = new SearchProductsRawResponse($this->authenticatedApi);
        $this->fetchShoppingCart = new FetchShoppingCart($this->authenticatedApi);
        $this->addProductToCart = new AddProductToCart($this->authenticatedApi);
        $this->addProductsToCart = new AddProductsToCart($this->authenticatedApi);
        $this->removeProductFromCart = new RemoveProductFromCart($this->authenticatedApi);
        $this->emptyShoppingCart = new EmptyShoppingCart($this->authenticatedApi);
        $this->selectDeliverySlotForCart = new SelectDeliverySlotForCart($this->authenticatedApi);
        $this->fetchAvailableDeliverySlots = new FetchAvailableDeliverySlots($this->authenticatedApi);
        $this->fetchAllShoppingLists = new FetchAllShoppingLists($this->authenticatedApi);
        $this->fetchShoppingListById = new FetchShoppingListById($this->authenticatedApi);
        $this->fetchShoppingListSublist = new FetchShoppingListSublist($this->authenticatedApi);
        $this->fetchDeliveryById = new FetchDeliveryById($this->authenticatedApi);
        $this->fetchDeliveryRoutingScenario = new FetchDeliveryRoutingScenario($this->authenticatedApi);
        $this->fetchDeliveryDriverPosition = new FetchDeliveryDriverPosition($this->authenticatedApi);
        $this->fetchAllDeliveries = new FetchAllDeliveries($this->authenticatedApi);
        $this->fetchCurrentDeliveries = new FetchCurrentDeliveries($this->authenticatedApi);
    }

    /**
     * Build a client, auto-discovering the PSR-18 client and PSR-17 factories
     * that are not passed explicitly.
     */
    public static function create(
        string $username,
        string $password,
        CountryCode|string $countryCode = CountryCode::NL,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        string $apiVersion = '15',
        ?string $authToken = null,
    ): self {
        $config = new PicnicConfig($countryCode, $apiVersion, authToken: $authToken);

        return new self($username, $password, Session::discover($config, $httpClient, $requestFactory, $streamFactory));
    }

    /**
     * Authenticate explicitly. Called lazily on the first request otherwise.
     *
     * @throws Exception\TwoFactorRequiredException when the account needs 2FA
     */
    public function authenticate(): self
    {
        $this->authenticatedApi->login();

        return $this;
    }

    /**
     * Request a 2FA code be sent. Call this after catching a
     * {@see Exception\TwoFactorRequiredException}, then {@see verifyTwoFactorCode()}.
     */
    public function requestTwoFactorCode(TwoFactorChannel|string $deliveryChannel = TwoFactorChannel::SMS): void
    {
        $channelName = $deliveryChannel instanceof TwoFactorChannel ? $deliveryChannel->value : strtoupper($deliveryChannel);
        $this->session->twoFactor('/user/2fa/generate', ['channel' => $channelName]);
    }

    /**
     * Complete login with the one-time code the user received.
     */
    public function verifyTwoFactorCode(string $oneTimeCode): void
    {
        $this->session->twoFactor('/user/2fa/verify', ['otp' => $oneTimeCode]);
    }

    /**
     * The current (rotating) auth token, so callers can cache it and pass it
     * back via the $authToken argument of {@see create()} to skip re-authenticating.
     */
    public function currentAuthToken(): ?string
    {
        return $this->session->authToken();
    }

    public function fetchLoggedInUser(): User
    {
        return $this->fetchLoggedInUser->execute();
    }

    /**
     * @return list<Product>
     */
    public function searchProductsByTerm(string $searchTerm): array
    {
        return $this->searchProducts->execute($searchTerm);
    }

    /**
     * @return array<mixed>
     */
    public function searchProductsRawResponse(string $searchTerm): array
    {
        return $this->searchProductsRawResponse->execute($searchTerm);
    }

    public function fetchShoppingCart(): Cart
    {
        return $this->fetchShoppingCart->execute();
    }

    public function addProductToCart(string $productId, int $quantity = 1): Cart
    {
        return $this->addProductToCart->execute($productId, $quantity);
    }

    /**
     * @param array<int|string, int> $quantitiesByProductId map of product id => quantity
     *
     * @throws InvalidArgumentException when given an empty map
     */
    public function addMultipleProductsToCart(array $quantitiesByProductId): Cart
    {
        return $this->addProductsToCart->execute($quantitiesByProductId);
    }

    public function removeProductFromCart(string $productId, int $quantity = 1): Cart
    {
        return $this->removeProductFromCart->execute($productId, $quantity);
    }

    public function emptyShoppingCart(): Cart
    {
        return $this->emptyShoppingCart->execute();
    }

    public function selectDeliverySlotForCart(string $deliverySlotId): Cart
    {
        return $this->selectDeliverySlotForCart->execute($deliverySlotId);
    }

    /**
     * @return list<DeliverySlot>
     */
    public function fetchAvailableDeliverySlots(): array
    {
        return $this->fetchAvailableDeliverySlots->execute();
    }

    /**
     * @return array<mixed>
     */
    public function fetchAllShoppingLists(): array
    {
        return $this->fetchAllShoppingLists->execute();
    }

    /**
     * @return array<mixed>
     */
    public function fetchShoppingListById(string $shoppingListId): array
    {
        return $this->fetchShoppingListById->execute($shoppingListId);
    }

    /**
     * @return array<mixed>
     */
    public function fetchShoppingListSublist(string $shoppingListId, string $sublistId): array
    {
        return $this->fetchShoppingListSublist->execute($shoppingListId, $sublistId);
    }

    public function fetchDeliveryById(string $deliveryId): Delivery
    {
        return $this->fetchDeliveryById->execute($deliveryId);
    }

    /**
     * @return array<mixed>
     */
    public function fetchDeliveryRoutingScenario(string $deliveryId): array
    {
        return $this->fetchDeliveryRoutingScenario->execute($deliveryId);
    }

    /**
     * @return array<mixed>
     */
    public function fetchDeliveryDriverPosition(string $deliveryId): array
    {
        return $this->fetchDeliveryDriverPosition->execute($deliveryId);
    }

    /**
     * @return list<Delivery>
     */
    public function fetchAllDeliveries(): array
    {
        return $this->fetchAllDeliveries->execute();
    }

    /**
     * @return list<Delivery>
     */
    public function fetchCurrentDeliveries(): array
    {
        return $this->fetchCurrentDeliveries->execute();
    }
}
