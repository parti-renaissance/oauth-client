<?php

namespace EnMarche\OAuthClient;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

abstract class OAuthAuthenticator extends AbstractGuardAuthenticator
{
    private $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        $data = [
            'message' => 'Authentication Required.',
        ];

        if ($authException) {
            $data['message'] = $authException->getMessage();
        }

        return new JsonResponse($data, 401);
    }

    public function getCredentials(Request $request)
    {
        $credentials = trim(preg_replace('/^(?:\s+)?Bearer\s/', '', $request->headers->get('Authorization')));

        if (!$credentials) {
            throw new AuthenticationException('Invalid token');
        }

        return $credentials;
    }

    /**
     * @param ApiUser $user
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        try {
            $tokenInfo = \GuzzleHttp\json_decode(
                $this
                    ->client
                    ->request('GET', '/oauth/v2/tokeninfo', ['query' => ['access_token' => $credentials]])
                    ->getBody()
                    ->getContents(),
                true
            );

            $user->setRoles(array_map(
                function ($scope) {return 'ROLE_OAUTH_SCOPE_'.mb_strtoupper($scope); },
                $tokenInfo['scopes'])
            );

            if (!in_array('client_credentials', $tokenInfo['grant_types'])) {
                throw new AuthenticationException('Invalid grant_type', 0);
            }
        } catch (ServerException | ConnectException | TooManyRedirectsException $e) {
            throw new ServiceUnavailableHttpException('The OAuth server is not available', 0, $e);
        } catch (GuzzleException $e) {
            throw new AuthenticationException('Invalid token', 0, $e);
        }

        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return $this->start($request, $exception);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
    }

    public function supportsRememberMe()
    {
        return false;
    }

    public function supports(Request $request)
    {
        return true;
    }
}
