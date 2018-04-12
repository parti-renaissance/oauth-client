<?php

namespace EnMarche\OAuthClient;

use GuzzleHttp\ClientInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\HttpFoundation\Request;

class OAuthAccessTokenProvider
{
    private $client;
    private $cache;
    private $oauthClientId;
    private $oauthClientSecret;
    private $scopes;

    private const ACCESS_TOKEN_CACHE_KEY = 'accessToken';

    public function __construct(ClientInterface $client, CacheInterface $cache, string $clientId, string $clientSecret, array $scopes = [])
    {
        $this->client = $client;
        $this->cache = $cache;
        $this->oauthClientId = $clientId;
        $this->oauthClientSecret = $clientSecret;
        $this->scopes = $scopes;
    }

    public function requestAccessToken(): string
    {
        $accessToken = $this->cache->get(self::ACCESS_TOKEN_CACHE_KEY);

        if ($accessToken) {
            return $accessToken;
        }

        $result = $this->client->request(
            Request::METHOD_POST,
            '/oauth/v2/token',
            [
                'form_params' => [
                    'client_id' => $this->oauthClientId,
                    'client_secret' => $this->oauthClientSecret,
                    'grant_type' => 'client_credentials',
                    'scope' => implode(' ', $this->scopes),
                ],
            ]
        );
        $accessToken = \GuzzleHttp\json_decode($result->getBody()->getContents(), true);

        $this->cache->set(
            self::ACCESS_TOKEN_CACHE_KEY,
            $accessToken['access_token'],
            $accessToken['expires_in'] - 10
        );

        return $accessToken['access_token'];
    }

    public function forgetAccessToken(): void
    {
        $this->cache->delete(self::ACCESS_TOKEN_CACHE_KEY);
    }
}
