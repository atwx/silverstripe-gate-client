<?php

namespace Atwx\SilverGateClient\Tests\Unit;

use ReflectionClass;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Security\Member;
use SilverStripe\Security\DefaultAdminService;
use SilverStripe\Security\LoginAttempt;
use SilverStripe\ORM\FieldType\DBDatetime;
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

    public function testFindAndLogInMemberResetsLockout()
    {
        Config::modify()->set(Member::class, 'lock_out_after_incorrect_logins', 3);
        Config::modify()->set(Member::class, 'lock_out_delay_mins', 15);

        $member = $this->createLockedOutMember('locked@example.test');
        $this->assertTrue($member->isLockedOut());

        Config::modify()->set(LoginService::class, 'member_id', $member->ID);

        $returned = LoginService::singleton()->findAndLogInMember();

        $this->assertInstanceOf(Member::class, $returned);
        $this->assertEquals($member->ID, Security::getCurrentUser()?->ID);
        $this->assertFalse(Member::get()->byID($member->ID)->isLockedOut());
    }

    public function testFindAndLogInMemberKeepsLockoutWhenResetDisabled()
    {
        Config::modify()->set(Member::class, 'lock_out_after_incorrect_logins', 3);
        Config::modify()->set(Member::class, 'lock_out_delay_mins', 15);
        Config::modify()->set(LoginService::class, 'reset_lockout', false);

        $member = $this->createLockedOutMember('stilllocked@example.test');

        Config::modify()->set(LoginService::class, 'member_id', $member->ID);

        $this->assertNull(LoginService::singleton()->findAndLogInMember());
        $this->assertNull(Security::getCurrentUser());
    }

    protected function createLockedOutMember(string $email): Member
    {
        $member = Member::create([
            'Email' => $email,
            'FirstName' => 'Locked',
            'Surname' => 'Out'
        ]);
        $member->LockedOutUntil = DBDatetime::create()->setValue(time() + 900)->Rfc2822();
        $member->FailedLoginCount = 3;
        $member->write();

        // Failed attempts happened before the JWT login
        $created = date('Y-m-d H:i:s', time() - 60);
        for ($i = 0; $i < 3; $i++) {
            $attempt = LoginAttempt::create();
            $attempt->Created = $created;
            $attempt->MemberID = $member->ID;
            $attempt->Status = LoginAttempt::FAILURE;
            $attempt->Email = $email;
            $attempt->write();
        }

        return $member;
    }

    public function testAddLeadingSlash()
    {
        $service = LoginService::singleton();
        $method = (new ReflectionClass(LoginService::class))->getMethod('addLeadingSlash');

        $this->assertEquals('/path/to/dest', $method->invoke($service, 'path/to/dest'));
        $this->assertEquals('/already/has/slash', $method->invoke($service, '/already/has/slash'));
        $this->assertEquals('/', $method->invoke($service, ''));
        $this->assertEquals('/', $method->invoke($service, '/'));
    }
}
