# EM OAuth Client

[![CircleCI](https://circleci.com/gh/EnMarche/oauth-client.svg?style=svg&circle-token=8c92ee1b91c05d332c601b4274ec0193128dc796)](https://circleci.com/gh/EnMarche/oauth-client)

## Installation

Add this repository to your composer.json as a repository as it is not registered on packagist :

```
{
    [...]
    "repositories": [
        {
            "type": "github",
            "url": "git@github.com:EnMarche/mailer-bundle.git"
        }
    ],
    [...]
{
```

Then you can install the lib

```
composer require enmarche/oauth-client 
```

## Example of integration with Symfony

This example use [csa/guzzle-bundle](https://github.com/csarrazi/CsaGuzzleBundle)

```yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    EnMarche\OAuthClient\OAuthAccessTokenProvider:
        arguments:
            $client: '@csa_guzzle.client.auth_server'
            $cache: '@app.cache.access_token.simple'

    EnMarche\OAuthClient\Guzzle\OAuthMiddleware:
        tags: [{name: 'csa_guzzle.middleware', alias: 'oauth', priority: 100}]
        
    app.cache.access_token.simple:
        class: Symfony\Component\Cache\Simple\Psr6Cache
        arguments:
            - '@app.cache.access_token'
```

The csa/guzzle-bundle and cache pool configs can look like the following :

```yaml
framework:
    cache:
        app: cache.adapter.apcu
        pools:
            app.cache.access_token:
                default_lifetime: 3500
                
csa_guzzle:
    [...]
    clients:
        auth_server:
            middleware: ['!oauth']
            config:
                base_uri: '%env(OAUTH_SERVER_URL)%'
                timeout: 2.0
                headers:
                    Accept: 'application/json'

        api_em:
            config:
                base_uri: '%env(API_EM_URL)%'
                timeout: 10.0
                headers:
                    Accept: 'application/json'
```

The OAuth middleware is the one that add the Authorization, that's why it is disabled for `auth_server` client. All
the others clients get the middleware activated automatically.

## Tests

```
make test
```
