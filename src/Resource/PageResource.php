<?php

declare(strict_types=1);

namespace PhPicnic\Resource;

use PhPicnic\Action\FetchBootstrap;
use PhPicnic\Action\FetchFaq;
use PhPicnic\Action\FetchPage;
use PhPicnic\Action\FetchRscPage;
use PhPicnic\Action\FetchSearchEmptyState;
use PhPicnic\Action\ResolveDeeplink;
use PhPicnic\ClientIdentity;
use PhPicnic\Dto\RscPage;
use PhPicnic\Dto\UiTree;
use PhPicnic\Enum\PageId;
use PhPicnic\LazyLoginApi;

/**
 * Fusion and RSC pages, bootstrap and content: `$picnic->pages()`. Pages only exist on API version 15. The three RSC pages need app version 1.246.1 or newer (the default); with an older profile pass `ClientIdentity::forProfile(AppProfile::V1_246_1)` as the identity.
 */
final readonly class PageResource
{
    private FetchBootstrap $fetchBootstrap;

    private FetchPage $fetchPage;

    private FetchRscPage $fetchRscPage;

    private ResolveDeeplink $resolveDeeplink;

    private FetchFaq $fetchFaq;

    private FetchSearchEmptyState $fetchSearchEmptyState;

    public function __construct(LazyLoginApi $api)
    {
        $this->fetchBootstrap = new FetchBootstrap($api);
        $this->fetchPage = new FetchPage($api);
        $this->fetchRscPage = new FetchRscPage($api);
        $this->resolveDeeplink = new ResolveDeeplink($api);
        $this->fetchFaq = new FetchFaq($api);
        $this->fetchSearchEmptyState = new FetchSearchEmptyState($api);
    }

    /**
     * @return array<mixed>
     */
    public function fetchBootstrap(): array
    {
        return $this->fetchBootstrap->execute();
    }

    /**
     * @param array<string, string> $query
     */
    public function fetchPage(PageId|string $pageId, array $query = [], ?ClientIdentity $identityOverride = null): UiTree
    {
        return $this->fetchPage->execute($pageId, $query, $identityOverride);
    }

    /**
     * @param array<string, string> $query
     */
    public function fetchRscPage(PageId|string $pageId, array $query = [], ?ClientIdentity $identityOverride = null): RscPage
    {
        return $this->fetchRscPage->execute($pageId, $query, $identityOverride);
    }

    public function resolveDeeplink(string $url): string
    {
        return $this->resolveDeeplink->execute($url);
    }

    public function fetchFaq(): UiTree
    {
        return $this->fetchFaq->execute();
    }

    public function fetchSearchEmptyState(): UiTree
    {
        return $this->fetchSearchEmptyState->execute();
    }

    public function fetchHome(): UiTree
    {
        return $this->fetchPage->execute(PageId::HOME);
    }

    public function fetchPurchases(): UiTree
    {
        return $this->fetchPage->execute(PageId::PURCHASES);
    }

    public function fetchSlotSelector(): UiTree
    {
        return $this->fetchPage->execute(PageId::SLOT_SELECTOR);
    }

    public function fetchParcelsOverview(): UiTree
    {
        return $this->fetchPage->execute(PageId::PARCELS_OVERVIEW);
    }

    public function fetchEmptySearch(): UiTree
    {
        return $this->fetchPage->execute(PageId::EMPTY_SEARCH);
    }

    public function fetchParcelTracking(string $parcelId): UiTree
    {
        return $this->fetchPage->execute(PageId::PARCEL_TRACKING, ['parcel_id' => $parcelId]);
    }

    /**
     * @param ClientIdentity|null $identityOverride an app version that serves this page as RSC
     */
    public function fetchCategoryTree(?ClientIdentity $identityOverride = null): RscPage
    {
        return $this->fetchRscPage->execute(PageId::CATEGORY_TREE, [], $identityOverride);
    }

    /**
     * @param ClientIdentity|null $identityOverride an app version that serves this page as RSC
     */
    public function fetchProfile(?ClientIdentity $identityOverride = null): RscPage
    {
        return $this->fetchRscPage->execute(PageId::PROFILE, [], $identityOverride);
    }

    /**
     * @param ClientIdentity|null $identityOverride an app version that serves this page as RSC
     */
    public function fetchPromoGroupDeepDive(string $promoGroupId, ?ClientIdentity $identityOverride = null): RscPage
    {
        return $this->fetchRscPage->execute(PageId::PROMO_GROUP_DEEP_DIVE, ['promo_group_id' => $promoGroupId], $identityOverride);
    }
}
