<?php


namespace PhPicnic;


class Session
{
    /**
     * @var string[]
     */
    private $headers;
    /**
     * @var \GuzzleHttp\Client
     */
    private $client;

    public function __construct()
    {
        $this->headers = [
            "User-Agent" => "okhttp/3.9.0",
            "Content-Type" => "application/json; charset=UTF-8"
        ];
        return $this;
    }

    public function login($username, $password, $baseUrl)
    {
        if ($this->headerExists('x-picnic-auth')) {
            $this->removeHeaderByKey('x-picnic-auth');
        }

        $url = $baseUrl . "/user/login";
        $secret = md5(utf8_encode($password));
        $data = [
            "key" => $username,
            "secret" => $secret,
            "client_id" => 1
        ];

        $response = $this->post($url, [
            'headers' => $this->headers,
            'json' => $data
        ]);

        if ($response) {
            $this->headers['x-picnic-auth'] = $response->getHeader('x-picnic-auth');
        }
        return $this;
    }

    /**
     * @param string $uri
     * @param array $options
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function post($uri = '', array $options = [])
    {
        // Extra headers
//        $options['debug'] = true;
        $options['headers'] = $this->headers;

        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', $uri, $options);
        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Something went wrong');
        }
        return $response;
    }

    /**
     * @param string $uri
     * @param array $options
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function get($uri = '', array $options = [])
    {
        // Extra headers
//        $options['debug'] = true;
        $options['headers'] = $this->headers;

        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', $uri, $options);
        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Something went wrong');
        }
        return $response;
    }

    private function removeHeaderByKey($key)
    {
        unset($this->headers[$key]);
    }

    /**
     * @param $name
     * @return bool
     */
    private function headerExists($name)
    {
        return array_key_exists($name, $this->headers);
    }
}