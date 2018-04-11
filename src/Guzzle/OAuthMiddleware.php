<?php

namespace EnMarche\OAuthClient\Guzzle;

use EnMarche\OAuthClient\OAuthAccessTokenProvider;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class OAuthMiddleware
{
    private $accessTokenProvider;
    private $logger;

    public function __construct(OAuthAccessTokenProvider $accessTokenProvider, LoggerInterface $logger)
    {
        $this->accessTokenProvider = $accessTokenProvider;
        $this->logger = $logger;
    }

    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $accessToken = $this->accessTokenProvider->requestAccessToken();
            $request = $request->withHeader('Authorization', "Bearer $accessToken");

            return $handler($request, $options)->then(function (ResponseInterface $response) {
                if (in_array($response->getStatusCode(), [Response::HTTP_FORBIDDEN, Response::HTTP_UNAUTHORIZED], true)) {
                    // Forget access token to prevent the same error to happen again and again because the access token was revoked
                    $this->accessTokenProvider->forgetAccessToken();

                    $this->logger->error('Request failed because access token was invalid', ['response' => $response]);
                }

                return $response;
            });
        };
    }
}
