<?php

namespace Atwx\SilverGateClient\Tests\Unit;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Security\Member;
use SilverStripe\Security\DefaultAdminService;
use Atwx\SilverGateClient\Services\LoginService;
use SilverStripe\Security\Security;

class LoginServiceTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure configs are clean for each test
        Config::modify()->set(LoginService::class, 'member_id', null);
        Config::modify()->set(LoginService::class, 'member_email', null);
        Config::modify()->set(LoginService::class, 'login_as_default_admin', false);
        Config::modify()->set(LoginService::class, 'login_dest', '');

        $this->logOut();
        Member::get()->removeAll();
    }

    public function testFindMemberById()
    {
        $member = Member::create([
            'Email' => 'byid@example1.test',
            'FirstName' => 'By',
            'Surname' => 'Id'
        ]);
        $member->write();
        $member = Member::create([
            'Email' => 'byid@example2.test',
            'FirstName' => 'By',
            'Surname' => 'Id'
        ]);
        $member->write();

        Config::modify()->set(LoginService::class, 'member_id', $member->ID);

        $found = LoginService::singleton()->findMember();

        $this->assertInstanceOf(Member::class, $found);
        $this->assertEquals($member->ID, $found->ID);
    }

    public function testFindMemberByEmail()
    {
        $member = Member::create([
            'Email' => 'byemail@example1.test',
            'FirstName' => 'By',
            'Surname' => 'Email'
        ]);
        $member->write();
        $member = Member::create([
            'Email' => 'byemail@example2.test',
            'FirstName' => 'By',
            'Surname' => 'Email'
        ]);
        $member->write();

        Config::modify()->set(LoginService::class, 'member_email', 'byemail@example2.test');

        $found = LoginService::singleton()->findMember();

        $this->assertInstanceOf(Member::class, $found);
        $this->assertEquals($member->Email, $found->Email);
    }

    public function testFindMemberDefaultsToAdminWhenConfigured()
    {
        $member = Member::create([
            'Email' => 'byemail@example1.test',
            'FirstName' => 'By',
            'Surname' => 'Email'
        ]);
        $member->write();
        $admin = DefaultAdminService::singleton()->findOrCreateDefaultAdmin();
        $admin->FirstName = 'admingenerated@example.test';
        $admin->write();

        Config::modify()->set(LoginService::class, 'login_as_default_admin', true);

        $found = LoginService::singleton()->findMember();

        $this->assertInstanceOf(Member::class, $found);
        $this->assertEquals('admingenerated@example.test', $found->FirstName);
    }

    public function testFindAndLogInMemberLogsIntoIdentityStore()
    {
        $member = Member::create([
            'Email' => 'loginme@example.test',
            'FirstName' => 'Log',
            'Surname' => 'In'
        ]);
        $member->write();

        Config::modify()->set(LoginService::class, 'member_id', $member->ID);

        $returned = LoginService::singleton()->findAndLogInMember();

        $current = Security::getCurrentUser();

        $this->assertInstanceOf(Member::class, $returned);
        $this->assertEquals($member->ID, $returned->ID);
        $this->assertInstanceOf(Member::class, $current);
        $this->assertEquals($member->ID, $current->ID);
    }
}
