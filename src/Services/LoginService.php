<?php

namespace Atwx\SilverGateClient\Services;

use SilverStripe\Admin\AdminRootController;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\DefaultAdminService;
use SilverStripe\Security\IdentityStore;
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
    private static bool $login_as_default_admin = false;

    /**
     * The URL to redirect to after a successful login.
     *
     * @config
     */
    private static string $login_dest = '';

    public function findAndLogInMember(): ?Member
    {
        $member = $this->findMember();
        if ($member && $member->canLogin()) {
            $identityStore = Injector::inst()->get(IdentityStore::class);
            $identityStore->logIn($member, false);
            return $member;
        }
        return null;
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
