<?php

declare(strict_types=1);

namespace PhPicnic\Tests\Resource;

use InvalidArgumentException;
use Nyholm\Psr7\Response;
use PhPicnic\Client;
use PhPicnic\ClientIdentity;
use PhPicnic\Dto\RscPage;
use PhPicnic\Enum\AppProfile;
use PhPicnic\Enum\ImageSize;
use PhPicnic\Exception\CheckoutIssueException;
use PhPicnic\Exception\UnexpectedResponseFormatException;
use PhPicnic\Tests\Support\AbstractPicnicTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class SpecialFlowsTest extends AbstractPicnicTestCase
{
    private const string RSC = "0:[\"$\",\"div\",null,{\"children\":\"hi\"}]\n1:I[\"chunk\"]\nnot a row\n";

    public function testRscPageParsesRowsAndModules(): void
    {
        $page = RscPage::fromText(self::RSC);

        self::assertSame(['0' => ['$', 'div', null, ['children' => 'hi']]], $page->rows);
        self::assertSame(['1' => ['chunk']], $page->modules);
        self::assertSame(self::RSC, $page->raw);
    }

    public function testFetchCategoryTreeWithANewerProfileReturnsAnRscPage(): void
    {
        $this->queueText(self::RSC, 'text/x-component');

        $page = $this->makeClient(cachedAuthToken: 'tok')->pages()->fetchCategoryTree(ClientIdentity::forProfile(AppProfile::V1_246_1));

        self::assertSame('/api/15/pages/category-tree-root', $this->sentRequest(0)->getUri()->getPath());
        self::assertSame('30100;1.246.1-15599;', $this->sentRequest(0)->getHeaderLine('x-picnic-agent'));
        self::assertArrayHasKey('0', $page->rows);
    }

    public function testFetchRscPageThrowsWhenPicnicAnswersWithJsonInstead(): void
    {
        $this->queueText('{"layout":{}}', 'application/json');

        $this->expectException(UnexpectedResponseFormatException::class);

        $this->makeClient(cachedAuthToken: 'tok')->pages()->fetchProfile();
    }

    public function testFetchPageThrowsATypedExceptionWhenPicnicAnswersWithRsc(): void
    {
        $this->queueText(self::RSC, 'text/x-component');

        try {
            $this->makeClient(cachedAuthToken: 'tok')->pages()->fetchPage('profile-root');
            self::fail('Expected UnexpectedResponseFormatException.');
        } catch (UnexpectedResponseFormatException $unexpectedResponseFormatException) {
            self::assertSame('/pages/profile-root', $unexpectedResponseFormatException->path);
            self::assertStringContainsString('text/x-component', $unexpectedResponseFormatException->contentType);
        }
    }

    public function testFetchPromoGroupDeepDiveSendsThePromoGroupId(): void
    {
        $this->queueText(self::RSC, 'text/x-component');

        $this->makeClient(cachedAuthToken: 'tok')->pages()->fetchPromoGroupDeepDive('promo-7');

        self::assertSame('/api/15/pages/promo-group-deep-dive?promo_group_id=promo-7', $this->sentRequest(0)->getUri()->getPath() . '?' . $this->sentRequest(0)->getUri()->getQuery());
    }

    public function testFindIdByGtinFollowsRedirectsUntilTheArticleIdAppears(): void
    {
        $this->http->addResponse(new Response(302, ['Location' => '/nl/qr/step-two']));
        $this->http->addResponse(new Response(302, ['Location' => 'https://picnic.app/nl/article;id=s1234567']));

        $productId = $this->makeClient(cachedAuthToken: 'tok')->products()->findIdByGtin('8712345678901');

        self::assertSame('s1234567', $productId);
        self::assertSame('https://picnic.app/nl/qr/gtin/8712345678901', (string) $this->sentRequest(0)->getUri());
        self::assertSame('https://picnic.app/nl/qr/step-two', (string) $this->sentRequest(1)->getUri());
        self::assertFalse($this->sentRequest(0)->hasHeader('x-picnic-auth'));
        self::assertFalse($this->sentRequest(1)->hasHeader('x-picnic-auth'));
    }

    public function testFindIdByGtinReturnsNullForAnUnknownBarcode(): void
    {
        $this->http->addResponse(new Response(302, ['Location' => 'https://picnic.app/nl/link/store/storefront']));

        self::assertNull($this->makeClient(cachedAuthToken: 'tok')->products()->findIdByGtin('8712345678901'));
    }

    public function testFindIdByGtinReturnsNullWhenThereIsNoRedirect(): void
    {
        $this->http->addResponse(new Response(200));

        self::assertNull($this->makeClient(cachedAuthToken: 'tok')->products()->findIdByGtin('8712345678901'));
    }

    public function testFindIdByGtinStopsAfterFiveRedirects(): void
    {
        for ($hop = 0; $hop < 8; ++$hop) {
            $this->http->addResponse(new Response(302, ['Location' => '/nl/loop']));
        }

        self::assertNull($this->makeClient(cachedAuthToken: 'tok')->products()->findIdByGtin('8712345678901'));
        self::assertCount(6, $this->http->getRequests());
    }

    public function testFindIdByGtinNeverFollowsARedirectToAnotherHost(): void
    {
        $this->http->addResponse(new Response(302, ['Location' => 'https://evil.example/steal']));

        $this->expectException(InvalidArgumentException::class);

        $this->makeClient(cachedAuthToken: 'tok')->products()->findIdByGtin('8712345678901');
    }

    public function testFindIdByGtinRejectsAMalformedBarcode(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeClient(cachedAuthToken: 'tok')->products()->findIdByGtin('12ab');
    }

    public function testFetchImageDownloadsFromTheStaticHostWithoutToken(): void
    {
        $this->queueText('PNGBYTES', 'image/png');

        $bytes = $this->makeClient(cachedAuthToken: 'tok')->products()->fetchImage('recipes/abc', ImageSize::LARGE);

        self::assertSame('PNGBYTES', $bytes);
        self::assertSame('https://storefront-prod.nl.picnicinternational.com/static/images/recipes/abc/large.png', (string) $this->sentRequest(0)->getUri());
        self::assertFalse($this->sentRequest(0)->hasHeader('x-picnic-auth'));
    }

    public function testStartCheckoutThrowsACheckoutIssueForAnAgeCheck(): void
    {
        $this->queueJson([
            'error' => [
                'code' => 'CART_HAS_ISSUES',
                'message' => 'Age check needed',
                'details' => ['type' => 'LEGACY_ALCOHOL_AGE_VERIFICATION_REQUIRED', 'blocking' => false, 'resolve_key' => 'rk-1'],
            ],
        ], 400);

        try {
            $this->makeClient(cachedAuthToken: 'tok')->checkout()->start(1700000000000);
            self::fail('Expected CheckoutIssueException.');
        } catch (CheckoutIssueException $checkoutIssueException) {
            self::assertTrue($checkoutIssueException->isAgeVerificationRequired());
            self::assertSame('rk-1', $checkoutIssueException->resolveKey);
        }
    }

    #[DataProvider('invalidArguments')]
    public function testInvalidArgumentsAreRejectedBeforeAnyRequest(callable $call): void
    {
        $this->expectException(InvalidArgumentException::class);

        try {
            $call($this->makeClient(cachedAuthToken: 'tok'));
        } finally {
            self::assertCount(0, $this->http->getRequests());
        }
    }

    /**
     * @return iterable<string, array{callable(Client): void}>
     */
    public static function invalidArguments(): iterable
    {
        yield 'rating above 10' => [static function (Client $client): void {
            $client->deliveries()->rate('d-1', 11);
        }];
        yield 'rating below 0' => [static function (Client $client): void {
            $client->deliveries()->rate('d-1', -1);
        }];
        yield 'wallet page 0' => [static function (Client $client): void {
            $client->payments()->fetchWalletTransactions(0);
        }];
        yield 'empty bulk add' => [static function (Client $client): void {
            $client->cart()->addMultipleProducts([]);
        }];
    }
}
