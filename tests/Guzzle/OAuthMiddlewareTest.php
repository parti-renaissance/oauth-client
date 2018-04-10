<?php

namespace EnMarche\OAuthClient\Tests;

use EnMarche\OAuthClient\Guzzle\OAuthMiddleware;
use EnMarche\OAuthClient\OAuthAccessTokenProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class OAuthMiddlewareTest extends TestCase
{
    public function testItAddsAuthorizationHeader()
    {
        $mockHandler = new MockHandler([new Response()]);
        $handler = HandlerStack::create($mockHandler);

        $tokenProvider = $this->createMock(OAuthAccessTokenProvider::class);
        $tokenProvider
            ->expects($this->once())
            ->method('requestAccessToken')
            ->willReturn('one_token')
        ;
        $logger = $this->createMock(LoggerInterface::class);

        $handler->push(new OAuthMiddleware($tokenProvider, $logger));

        $client = new Client(['handler' => $handler]);

        $client->get('http://foo.bar');

        $request = $mockHandler->getLastRequest();
        self::assertSame(['Bearer one_token'], $request->getHeader('Authorization'));
    }

    /**
     * @dataProvider providesTestItDropsInvalidToken
     * @expectedException \GuzzleHttp\Exception\ClientException
     */
    public function testItDropsInvalidToken(int $responseStatusCode)
    {
        $mockHandler = new MockHandler([new Response($responseStatusCode)]);
        $handler = HandlerStack::create($mockHandler);

        $tokenProvider = $this->createMock(OAuthAccessTokenProvider::class);
        $tokenProvider
            ->expects($this->once())
            ->method('forgetAccessToken')
        ;

        $logger = $this->createMock(LoggerInterface::class);

        $handler->push(new OAuthMiddleware($tokenProvider, $logger));

        $client = new Client(['handler' => $handler]);

        $client->get('http://foo.bar');
    }

    public function providesTestItDropsInvalidToken(): array
    {
        return [[401], [403]];
    }
}
