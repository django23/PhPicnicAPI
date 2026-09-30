<?php

declare(strict_types=1);

namespace PhPicnic\Resource;

use PhPicnic\Action\CheckForUpdates;
use PhPicnic\Action\FetchProfileMenu;
use PhPicnic\Action\FetchUserInfo;
use PhPicnic\Action\LogOut;
use PhPicnic\Action\RegisterPushToken;
use PhPicnic\Action\RequestPhoneVerificationCode;
use PhPicnic\Action\SetBusinessDetails;
use PhPicnic\Action\SetHouseholdDetails;
use PhPicnic\Action\SubmitSuggestion;
use PhPicnic\Action\SubscribeToPush;
use PhPicnic\Action\VerifyPhoneNumber;
use PhPicnic\Dto\UpdateCheckResult;
use PhPicnic\Dto\UserInfo;
use PhPicnic\LazyLoginApi;

/**
 * Account, profile and onboarding: `$picnic->account()`. The plain user is `$picnic->fetchLoggedInUser()`.
 */
final readonly class AccountResource
{
    private FetchUserInfo $fetchUserInfo;

    private FetchProfileMenu $fetchProfileMenu;

    private LogOut $logOut;

    private SubmitSuggestion $submitSuggestion;

    private RegisterPushToken $registerPushToken;

    private CheckForUpdates $checkForUpdates;

    private RequestPhoneVerificationCode $requestPhoneVerificationCode;

    private VerifyPhoneNumber $verifyPhoneNumber;

    private SetHouseholdDetails $setHouseholdDetails;

    private SetBusinessDetails $setBusinessDetails;

    private SubscribeToPush $subscribeToPush;

    public function __construct(LazyLoginApi $api)
    {
        $this->fetchUserInfo = new FetchUserInfo($api);
        $this->fetchProfileMenu = new FetchProfileMenu($api);
        $this->logOut = new LogOut($api);
        $this->submitSuggestion = new SubmitSuggestion($api);
        $this->registerPushToken = new RegisterPushToken($api);
        $this->checkForUpdates = new CheckForUpdates($api);
        $this->requestPhoneVerificationCode = new RequestPhoneVerificationCode($api);
        $this->verifyPhoneNumber = new VerifyPhoneNumber($api);
        $this->setHouseholdDetails = new SetHouseholdDetails($api);
        $this->setBusinessDetails = new SetBusinessDetails($api);
        $this->subscribeToPush = new SubscribeToPush($api);
    }

    public function fetchInfo(): UserInfo
    {
        return $this->fetchUserInfo->execute();
    }

    /**
     * @return array<mixed>
     */
    public function fetchProfileMenu(): array
    {
        return $this->fetchProfileMenu->execute();
    }

    public function logout(): void
    {
        $this->logOut->execute();
    }

    public function submitSuggestion(string $suggestion): void
    {
        $this->submitSuggestion->execute($suggestion);
    }

    public function registerPushToken(string $pushToken, string $platform): void
    {
        $this->registerPushToken->execute($pushToken, $platform);
    }

    public function checkForUpdates(): UpdateCheckResult
    {
        return $this->checkForUpdates->execute();
    }

    public function requestPhoneVerificationCode(string $phoneNumber): void
    {
        $this->requestPhoneVerificationCode->execute($phoneNumber);
    }

    public function verifyPhoneNumber(string $phoneNumber, string $oneTimeCode): void
    {
        $this->verifyPhoneNumber->execute($phoneNumber, $oneTimeCode);
    }

    /**
     * @param array<string, mixed> $householdDetails
     */
    public function setHouseholdDetails(array $householdDetails): void
    {
        $this->setHouseholdDetails->execute($householdDetails);
    }

    /**
     * @param array<string, mixed> $businessDetails
     */
    public function setBusinessDetails(array $businessDetails): void
    {
        $this->setBusinessDetails->execute($businessDetails);
    }

    /**
     * @param list<string> $topics
     */
    public function subscribeToPush(array $topics): void
    {
        $this->subscribeToPush->execute($topics);
    }
}
