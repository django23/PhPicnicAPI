<?php

declare(strict_types=1);

namespace PhPicnic;

use PhPicnic\Dto\Cart;
use PhPicnic\Dto\Delivery;
use PhPicnic\Dto\DeliverySlot;
use PhPicnic\Dto\Product;
use PhPicnic\Dto\User;
use PhPicnic\Enum\CountryCode;
use PhPicnic\Enum\TwoFactorChannel;
use PhPicnic\Search\SearchResultParser;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * High-level client for the (unofficial) Picnic API.
 *
 * This library is not affiliated with Picnic and talks to the endpoints of the
 * mobile application. Use at your own risk.
 */
final class Client
{
    private readonly Session $session;

    private bool $loggedIn;

    public function __construct(
        private readonly string $username,
        private readonly string $password,
        CountryCode|string $countryCode = CountryCode::NL,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        string $apiVersion = '15',
        ?string $authToken = null,
    ) {
        $config = new PicnicConfig($countryCode, $apiVersion, authToken: $authToken);
        $this->session = new Session($config, $httpClient, $requestFactory, $streamFactory);
        $this->loggedIn = $this->session->isAuthenticated();
    }

    /**
     * Authenticate explicitly. Called lazily on the first request otherwise.
     *
     * @throws Exception\TwoFactorRequiredException when the account needs 2FA
     */
    public function login(): self
    {
        $this->session->login($this->username, $this->password);
        $this->loggedIn = true;

        return $this;
    }

    /**
     * Request a 2FA code be sent. Call this after catching a
     * {@see Exception\TwoFactorRequiredException}, then {@see verify2FA()}.
     */
    public function generate2FA(TwoFactorChannel|string $channel = TwoFactorChannel::SMS): void
    {
        $channel = $channel instanceof TwoFactorChannel ? $channel->value : strtoupper($channel);
        $this->session->twoFactor('/user/2fa/generate', ['channel' => $channel]);
    }

    /**
     * Complete login with the received OTP code.
     */
    public function verify2FA(string $code): void
    {
        $this->session->twoFactor('/user/2fa/verify', ['otp' => $code]);
        $this->loggedIn = $this->session->isAuthenticated();
    }

    /**
     * The current (rotating) auth token, so callers can cache it and pass it
     * back via the $authToken constructor argument to skip re-authenticating.
     */
    public function getAuthToken(): ?string
    {
        return $this->session->authToken();
    }

    public function getUser(): User
    {
        return User::fromArray($this->get('/user'));
    }

    /**
     * Search for products. Returns parsed products; use {@see searchRaw()} for
     * the untouched response tree.
     *
     * @return list<Product>
     */
    public function search(string $query): array
    {
        return SearchResultParser::parse($this->searchRaw($query));
    }

    /**
     * @return array<mixed>
     */
    public function searchRaw(string $query): array
    {
        return $this->get('/pages/search-page-results?search_term=' . rawurlencode($query));
    }

    public function getCart(): Cart
    {
        return Cart::fromArray($this->get('/cart'));
    }

    public function addProduct(string $productId, int $count = 1): Cart
    {
        return Cart::fromArray($this->post('/cart/add_product', [
            'product_id' => $productId,
            'count' => $count,
        ]));
    }

    /**
     * Add several products at once.
     *
     * Note: numeric product-id keys are stored as PHP int keys, but still
     * JSON-encode to the object Picnic expects ({ "<productId>": <quantity> }).
     *
     * @param array<int|string, int> $products map of product id => quantity
     *
     * @throws \InvalidArgumentException when given an empty map
     */
    public function addProducts(array $products): Cart
    {
        if ($products === []) {
            throw new \InvalidArgumentException('addProducts() requires at least one product.');
        }

        // Picnic expects a JSON object { "<productId>": <quantity>, ... }.
        return Cart::fromArray($this->post('/cart/products/add', $products));
    }

    public function removeProduct(string $productId, int $count = 1): Cart
    {
        return Cart::fromArray($this->post('/cart/remove_product', [
            'product_id' => $productId,
            'count' => $count,
        ]));
    }

    public function clearCart(): Cart
    {
        return Cart::fromArray($this->post('/cart/clear'));
    }

    public function setDeliverySlot(string $slotId): Cart
    {
        return Cart::fromArray($this->post('/cart/set_delivery_slot', ['slot_id' => $slotId]));
    }

    /**
     * @return list<DeliverySlot>
     */
    public function getDeliverySlots(): array
    {
        $data = $this->get('/cart/delivery_slots');
        $slots = isset($data['delivery_slots']) && is_array($data['delivery_slots']) ? $data['delivery_slots'] : $data;

        return $this->mapList($slots, DeliverySlot::fromArray(...));
    }

    /**
     * Fetch all shopping lists, or a single list when $listId is given.
     *
     * @return array<mixed>
     */
    public function getList(?string $listId = null): array
    {
        return $this->get($listId !== null ? '/lists/' . $listId : '/lists');
    }

    /**
     * @return array<mixed>
     */
    public function getSublist(string $listId, string $sublistId): array
    {
        return $this->get('/lists/' . $listId . '?sublist=' . rawurlencode($sublistId));
    }

    public function getDelivery(string $deliveryId): Delivery
    {
        // Picnic switched this endpoint from POST to GET.
        return Delivery::fromArray($this->get('/deliveries/' . $deliveryId));
    }

    /**
     * Live routing scenario for a delivery (a UI tree).
     *
     * @return array<mixed>
     */
    public function getDeliveryScenario(string $deliveryId): array
    {
        return $this->get('/deliveries/' . $deliveryId . '/scenario');
    }

    /**
     * Live driver position / ETA for a delivery (a UI tree).
     *
     * @return array<mixed>
     */
    public function getDeliveryPosition(string $deliveryId): array
    {
        return $this->get('/deliveries/' . $deliveryId . '/position');
    }

    /**
     * All deliveries. Picnic removed the unsummarized variant, so this always
     * posts to /deliveries/summary.
     *
     * @return list<Delivery>
     */
    public function getDeliveries(): array
    {
        return $this->mapList($this->post('/deliveries/summary', []), Delivery::fromArray(...));
    }

    /**
     * Deliveries that are current (placed but not yet delivered).
     *
     * @return list<Delivery>
     */
    public function getCurrentDeliveries(): array
    {
        return $this->mapList($this->post('/deliveries/summary', ['CURRENT']), Delivery::fromArray(...));
    }

    /**
     * @param array<mixed>           $items
     * @param callable(array<mixed>): T $factory
     *
     * @return list<T>
     *
     * @template T
     */
    private function mapList(array $items, callable $factory): array
    {
        $result = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $result[] = $factory($item);
            }
        }

        return $result;
    }

    /**
     * @return array<mixed>
     */
    private function get(string $path): array
    {
        $this->ensureAuthenticated();

        return $this->session->get($path);
    }

    /**
     * @param array<mixed>|string $data
     *
     * @return array<mixed>
     */
    private function post(string $path, array|string $data = []): array
    {
        $this->ensureAuthenticated();

        return $this->session->post($path, $data);
    }

    private function ensureAuthenticated(): void
    {
        if (! $this->loggedIn) {
            $this->login();
        }
    }
}
