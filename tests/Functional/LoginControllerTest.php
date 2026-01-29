<?php

namespace Atwx\SilverGateClient\Tests\Functional;

use Atwx\SilverGateClient\Services\LoginService;
use Atwx\SilverGateClient\Services\TokenService;
use Firebase\JWT\JWT;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\Security\Member;

class LoginControllerTest extends FunctionalTest
{
    protected $usesDatabase = true;

    protected static $privateKey1 = <<<'PEM'
-----BEGIN PRIVATE KEY-----
MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQCu6MmWJyQSUnwA
tjivKbDOo+AbL8jkDwd9JL0KOFipSPN9kjfLRbM/DmvWM1ozN4TySZ0SfC8Y+vlF
v3Q65FyRiCmMSUnl6bqCRjno+Y5PFkmB9kyVVuOFOXSK9YOZBAkihdA97CWEiv3G
j91iDvCp83rOD1aZQ5XOxlj+KstgBkqMnWKRjogtZS4KCVbzLa9M606y8hc1yUKK
FcsXD5asSxjjd4AdmQIOaCjQnLWcLBVyqhtoH0dpNwHvSRXNYbgIDkxPH93rBWGb
zQCgfwzQALugEMCmiK1KeDGMAGG8kVIGiaEZBm1yqF4lIoGPE0KQz3wBgWGIYoIz
wIq7eYWPAgMBAAECggEARom+8n0qgPEe7TKPvaR0l4FjWdN1kvO0s3Vjf4Glz++J
svSK58FePmnUd3gSQmF7jjK907ydSde9O7GwcCe0ZUSibN8JnF0cSHmF1Tz/dUmp
r23jCL8X1pyLMZc90THddTy99JsNrlxXaAKQ54Da8c8tkXiCFiE5g0Bel3IuvrRg
FUCLBxFzrZpkPXk/kQh9gq36aq7KnpXy7YyDhggJuJY9p1PogzTQTQLYsag6M3VG
CuDj3jS6Q82ErlpDU7AGJFt4omeZDqk5xmppuCKOgsDwhicoS9QEbW1qH52R6vMD
BwG7tCjguu/GjwbcULGmAR0yjmdmq+ueADO2DVS2oQKBgQDY/skvivIKr0YdZrCJ
/f5or0mV4cN+l/IZtkY0iv5IpWJTgdcYLNM2X/wy2GxsW3lMTNPLf25tvI2jNZkk
ZOSLIPIKwUd+OY23gsuKlhDP2mBCDDUv67Bz4K6UHFvEHfaSwyXt/46sxo4pJWMC
je2bPmxwvVQud6Sc4/Jx4k/41wKBgQDOWWIOpixWwgnkm2PVIUZNxaPDxnUnzO2Y
zU6tSNT2sTpEMbRmPo605tJYJDtAhN+PIJ34qqMDoQB2JbIsgyufCdkVgOcLJtHg
EkMwUN1Am6tAsOY3LusNImdOiyxq3C3Hv3qIP/RESXSj21Wdk+PIRB4VSRP8dmch
MS5Lq42qCQKBgQDP35RfpR2luq7Sb5NjPhy/sFwv5yzeUzUsCH9MynI6qaR+Fv41
PIvBpUX2d3m2fBLBUz/5zEO/gEe3OBtS0AxYc+ErCGkytHPcfsH6KzRDyhE4dHVn
SMOq3myfmmMWhxW1Fpl5W74UKcn6BMTKp6gddjlv7w97zEW3vm59Rq4R1QKBgQDL
wgNLx9b8jHpMXQyFhmfvn/uH9E33USpEuma4QbTZLEQG1rX2SEuOYmbOVmiT2yEO
Kf0TcRurF65m++4mehf24TiVPUXoAxs3EZF7Aj6X7595L7UERLYhsBkmu9LOakBi
f7c1F0HStF4S9yBhi4lfVbQG/LRZlWlcoz29xtcKUQKBgHGQFm8CktGPBeTC9kn/
SwhWZJk+s1Dj0OnQKofiYaTxqph7aGznWCbDiJ6Eg+83UkgWocr153adc6Ah5Agx
9NycxZbHoT5+qxI5CY65PIRsjb9CcH1gsYoWTudcKg2GENDhqpQb/eAMQeD5CqNV
GnnLWyg2w17426QQYSg0V68f
-----END PRIVATE KEY-----
PEM;

    protected static $publicKey1 = <<<'PEM'
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEArujJlickElJ8ALY4rymw
zqPgGy/I5A8HfSS9CjhYqUjzfZI3y0WzPw5r1jNaMzeE8kmdEnwvGPr5Rb90OuRc
kYgpjElJ5em6gkY56PmOTxZJgfZMlVbjhTl0ivWDmQQJIoXQPewlhIr9xo/dYg7w
qfN6zg9WmUOVzsZY/irLYAZKjJ1ikY6ILWUuCglW8y2vTOtOsvIXNclCihXLFw+W
rEsY43eAHZkCDmgo0Jy1nCwVcqobaB9HaTcB70kVzWG4CA5MTx/d6wVhm80AoH8M
0AC7oBDApoitSngxjABhvJFSBomhGQZtcqheJSKBjxNCkM98AYFhiGKCM8CKu3mF
jwIDAQAB
-----END PUBLIC KEY-----
PEM;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logOut();
        Member::get()->removeAll();
        Config::modify()->set(TokenService::class, 'public_key', self::$publicKey1);
    }

    public function testMalformedTokenReturns400()
    {
        // Provide a token that is not valid base64, so controller will return 400
        $response = $this->get('/_silvergateclient/token/invalid-token-!!!');
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testInvalidJwtReturns403()
    {
        $payload = 'sometoken';
        $b64 = urlencode(base64_encode($payload));

        $response = $this->get('/_silvergateclient/token/' . $b64);
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testNoMemberFoundReturns403()
    {
        $token = JWT::encode(
            ['iat' => time()],
            self::$privateKey1,
            'RS256'
        );
        $b64 = urlencode(base64_encode($token));
        $response = $this->get('/_silvergateclient/token/' . $b64);
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testSuccessfulLoginRedirectsToConfiguredUrl()
    {
        Config::modify()->set(LoginService::class, 'login_as_default_admin', true);
        $token = JWT::encode(
            ['iat' => time()],
            self::$privateKey1,
            'RS256'
        );
        $b64 = urlencode(base64_encode($token));

        $response = $this->get('/_silvergateclient/token/' . $b64);

        // Should be redirected to /admin
        $this->assertEquals(200, $response->getStatusCode());
        $redirectUrl = singleton(LoginService::class)->getRedirectUrl();
        $this->assertStringContainsString(
            '<meta http-equiv="refresh" content="0; url=' . $redirectUrl . '">',
            $response->getBody()
        );
    }
}
