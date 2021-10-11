<?php


namespace PhPicnic;


class Client
{
    private $username;
    private $password;
    private $countryCode;
    private $baseUrl;
    /**
     * @var Session
     */
    private $session;

    public function __construct($username, $password, $countryCode)
    {
        $this->username = $username;
        $this->password = $password;
        $this->countryCode = $countryCode;
        $this->baseUrl = $this->generateBaseUrl();

        $this->session = new Session();
        $this->session->login($this->username, $this->password, $this->baseUrl);
        return $this;
    }

    protected function generateBaseUrl()
    {
        return strtolower(str_replace('{}', $_ENV['country_code'], $_ENV['base_url'])) . $_ENV['api_version'];
    }

    protected function get($path)
    {
        $url = $this->baseUrl . $path;
        return json_decode((string)$this->session->get($url)->getBody(), true);
    }

    protected function post($path, $data)
    {
        $url = $this->baseUrl . $path;
        return json_decode((string)$this->session->post($url, ['json' => $data])->getBody(), true);
    }

    public function getUser()
    {
        return $this->get('/user');
    }

    public function search($query)
    {
        $path = '/search?search_term=' . $query;
        return $this->get($path);
    }

    public function getList($listId = null)
    {
        if ($listId) {
            $path = "/lists/" . $listId;
        } else {
            $path = "/lists/";
        }
        return $this->get($path);
    }

    public function getCart()
    {
        return $this->get('/cart');
    }

    public function addProduct($productId, $count = 1)
    {
        $data = [
            'product_id' => $productId,
            'count' => $count,
        ];
        return $this->post('/cart/add_product', $data);
    }

    public function removeProduct($productId, $count = 1)
    {
        $data = [
            'product_id' => $productId,
            'count' => $count,
        ];
        return $this->post('/cart/remove_product', $data);
    }

    public function clearCart()
    {
        return $this->post('/cart/clear');
    }

    public function getDeliverySlots()
    {
        return $this->get('/cart/delivery_slots');
    }

    public function getDelivery($deliveryId)
    {
        $path = '/deliveries/' . $deliveryId;
        $data = [];
        return $this->post($path, $data);
    }

    public function getDeliveries($summary = false)
    {
        $data = [];
        if ($summary) {
            return $this->post('/deliveries/summary', $data);
        }
        return $this->post('/deliveries', $data);
    }

    public function getCurrentDeliveries()
    {
        $data = "CURRENT";
        return $this->post('/deliveries/', $data);
    }

}