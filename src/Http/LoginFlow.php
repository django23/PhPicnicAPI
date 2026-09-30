<?php

declare(strict_types=1);

namespace PhPicnic\Http;

use PhPicnic\Credentials;
use PhPicnic\Enum\ApiEndpoint;
use PhPicnic\Exception\InvalidCredentialsException;
use PhPicnic\Exception\PicnicApiException;
use PhPicnic\Exception\TwoFactorRequiredException;
use PhPicnic\JsonResponseDecoder;
use PhPicnic\PicnicConfig;

/**
 * Exchanges credentials for a token. The token is not read from the login body:
 * it arrives in the x-picnic-auth response header like every rotation, so success
 * means "the holder has a token afterwards".
 */
final readonly class LoginFlow
{
    public function __construct(
        private PicnicConfig $config,
        private RequestBuilder $requests,
        private RequestSender $sender,
        private AuthTokenHolder $authToken,
    ) {
    }

    /**
     * @throws TwoFactorRequiredException when the account needs a second factor
     * @throws InvalidCredentialsException on bad credentials / missing token
     * @throws PicnicApiException
     */
    public function perform(Credentials $credentials): void
    {
        $this->authToken->forget();
        $loginPath = ApiEndpoint::LOGIN->path();

        $loginResponse = $this->sender->sendAuthenticated($this->requests->apiWithBody('POST', $loginPath, [
            'key' => $credentials->username,
            'secret' => $credentials->secret,
            'client_id' => $this->config->identity->clientId,
        ]));
        new AuthResponseGuard()->assertLoginAccepted(JsonResponseDecoder::decode($loginResponse, $loginPath));

        if (! $this->authToken->isPresent()) {
            throw new InvalidCredentialsException(
                'Login failed: the Picnic API did not return an auth token. Check your credentials.',
            );
        }
    }
}
