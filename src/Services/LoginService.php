<?php

namespace Atwx\SilverGateClient\Services;

use SilverStripe\Admin\AdminRootController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\DefaultAdminService;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Security\LoginAttempt;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;

class LoginService
{
    use Configurable;
    use Injectable;
    use Extensible;

    /**
     * The ID of the member to log in as.
     *
     * @config
     */
    private static ?int $member_id = null;

    /**
     * The email of the member to log in as.
     *
     * @config
     */
    private static ?string $member_email = null;

    /**
     * Whether to log in as the default admin user if member search not configured.
     * If no default admin exists, one will be created.
     *
     * @config
     */
    private static bool $login_as_default_admin = true;

    /**
     * Whether to disable sudo mode for JWT-authenticated sessions.
     * Can be overridden via SILVERGATECLIENT_DISABLE_SUDO_MODE env variable.
     *
     * @config
     */
    private static bool $disable_sudo_mode = false;

    /**
     * Whether a valid JWT login lifts a lockout caused by failed password logins.
     * The JWT proves the identity, so failed form attempts should not block it.
     *
     * @config
     */
    private static bool $reset_lockout = true;

    /**
     * The URL to redirect to after a successful login.
     *
     * @config
     */
    private static string $login_dest = '';

    public function isDisableSudoMode(): bool
    {
        return (bool) (Environment::getEnv('SILVERGATECLIENT_DISABLE_SUDO_MODE')
            ?: $this->config()->get('disable_sudo_mode'));
    }

    public function findAndLogInMember(?HTTPRequest $request = null): ?Member
    {
        $member = $this->findMember();
        if (!$member) {
            return null;
        }

        if ($this->config()->get('reset_lockout') && $member->isLockedOut()) {
            $this->resetLockout($member, $request);
        }

        if ($member->canLogin()) {
            $identityStore = Injector::inst()->get(IdentityStore::class);
            $identityStore->logIn($member, false, $request);
            return $member;
        }
        return null;
    }

    /**
     * Clears both lockout mechanisms: the LockedOutUntil field and the
     * LoginAttempt history, which is checked by Member::isLockedOut().
     */
    public function resetLockout(Member $member, ?HTTPRequest $request = null): void
    {
        $member->FailedLoginCount = 0;
        $member->LockedOutUntil = null;
        $member->write();

        // A successful attempt among the most recent ones ends the lockout
        $attempt = LoginAttempt::create();
        $attempt->MemberID = $member->ID;
        $attempt->Status = LoginAttempt::SUCCESS;
        $attempt->Email = $member->Email;
        $attempt->IP = $request?->getIP();
        $attempt->write();
    }

    public function findMember(): ?Member
    {
        $memberId = $this->config()->get('member_id');
        if ($memberId) {
            $member = Member::get()->byID($memberId);
            if ($member) {
                return $member;
                ;
            }
        }

        $memberEmail = $this->config()->get('member_email');
        if ($memberEmail) {
            $member = Member::get()->filter('Email', $memberEmail)->first();
            if ($member) {
                return $member;
                ;
            }
        }

        $loginAsDefaultAdmin = $this->config()->get('login_as_default_admin');
        if ($loginAsDefaultAdmin) {
            return DefaultAdminService::singleton()->findOrCreateDefaultAdmin();
        }

        return null;
    }

    public function getRedirectUrl()
    {
        $loginDest = $this->config()->get('login_dest');
        if ($loginDest) {
            return $this->addLeadingSlash($loginDest);
        }

        $defaultLoginDest = Security::config()->get('default_login_dest');
        if ($defaultLoginDest) {
            return $this->addLeadingSlash($defaultLoginDest);
        }

        $finalDest = Permission::check('CMS_ACCESS')
            ? AdminRootController::admin_url()
            : '/';

        return $this->addLeadingSlash($finalDest);
    }

    protected function addLeadingSlash(string $url): string
    {
        return '/' . ltrim($url, '/');
    }
}
