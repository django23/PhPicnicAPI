<?php

declare(strict_types=1);

namespace PhPicnic\Tests\Http;

use InvalidArgumentException;
use PhPicnic\Auth\InMemoryAuthTokenStore;
use PhPicnic\Http\AuthTokenHolder;
use PhPicnic\Http\RequestBuilder;
use PhPicnic\PicnicConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RequestBuilderTest extends TestCase
{
    private PicnicConfig $config;

    private RequestBuilder $builder;

    protected function setUp(): void
    {
        $this->config = new PicnicConfig();
        $this->builder = new RequestBuilder($this->config, new AuthTokenHolder(new InMemoryAuthTokenStore(), 'tok'));
    }

    public function testApiGetCarriesIdentityHeadersAndToken(): void
    {
        $request = $this->builder->apiGet('/user');

        self::assertSame('GET', $request->method);
        self::assertSame($this->config->location->baseUrl() . '/user', $request->url);
        self::assertSame('tok', $request->headers['x-picnic-auth']);
        self::assertSame($this->config->identity->picnicAgent, $request->headers['x-picnic-agent']);
        self::assertNull($request->encodedBody);
    }

    public function testApiWithBodyEncodesJsonAndNullSendsNoBody(): void
    {
        self::assertSame('[]', $this->builder->apiWithBody('POST', '/a', [])->encodedBody);
        self::assertSame('{"a":1}', $this->builder->apiWithBody('PUT', '/a', ['a' => 1])->encodedBody);
        self::assertNull($this->builder->apiWithBody('POST', '/a', null)->encodedBody);
    }

    public function testApiBytesOverridesContentType(): void
    {
        $request = $this->builder->apiBytes('/img', 'BYTES', 'image/png');

        self::assertSame('image/png', $request->headers['Content-Type']);
        self::assertSame('BYTES', $request->encodedBody);
    }

    public function testUnauthenticatedFamiliesNeverCarryTheToken(): void
    {
        $requests = [
            $this->builder->publicApiGet('/x'),
            $this->builder->staticFileGet('/x'),
            $this->builder->webGet('https://picnic.app/x'),
        ];

        foreach ($requests as $request) {
            self::assertArrayNotHasKey('x-picnic-auth', $request->headers);
        }
    }

    public function testPublicApiSendsOnlyTheCountry(): void
    {
        $headers = $this->builder->publicApiGet('/x')->headers;

        self::assertArrayHasKey('picnic-country', $headers);
        self::assertArrayNotHasKey('x-picnic-agent', $headers);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function nonRelativePaths(): iterable
    {
        yield 'no slash' => ['user'];
        yield 'protocol relative' => ['//evil.test/x'];
        yield 'full url' => ['https://evil.test/x'];
    }

    #[DataProvider('nonRelativePaths')]
    public function testOnlyRelativePathsAreAccepted(string $path): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->builder->apiGet($path);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function refusedUrls(): iterable
    {
        yield 'http' => ['http://picnic.app/x'];
        yield 'other host' => ['https://evil.test/x'];
        yield 'suffix trick' => ['https://notpicnic.app/x'];
    }

    #[DataProvider('refusedUrls')]
    public function testWebGetRefusesHostsOutsideTheAllowlist(string $url): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->builder->webGet($url);
    }

    public function testWebGetAllowsSubdomains(): void
    {
        self::assertSame('https://x.picnicinternational.com/a', $this->builder->webGet('https://x.picnicinternational.com/a')->url);
    }
}
