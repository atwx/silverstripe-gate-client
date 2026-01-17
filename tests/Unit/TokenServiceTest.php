<?php

namespace Atwx\SilverGateClient\Tests\Unit;

use SilverStripe\Core\Environment;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Core\Config\Config;
use Atwx\SilverGateClient\Services\TokenService;
use Firebase\JWT\JWT;

class TokenServiceTest extends SapphireTest
{
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

    protected static $privateKey2 = <<<'PEM'
-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDP12Rz8TckQcEg
qD379DHSTdug5rCZbpGILONS5h9KX4NfJkAPd8mg2YIz7OQjCTCwLH/r91trO0fd
i9ckG8fyzbr/JifrUlIbgnea3DaRdN5XqOpcXXWK85M15AJP+k7lEA83oKb5Ybbg
yxvOE1AfcfWi5zxXPVNyuaBQFdwWcWAc+RaedsdMfK7i5zTXrFMGQe/XDxtY+XT0
lHmJEeJQg0OfYfuVKo/n50stl7dUsdRBjJSmoEHqG2C/ZMPcVHzMj9V1r9dGt0gZ
eSofO2Bbtkl7DQYqrAmM152Hor13935DIGioRPgUTJQqkYWfP444E0p7yPWUrneX
DMfh6HufAgMBAAECggEAV2hMsSvlFkTm/R50LuU0HK8sqf7Kj0Q+RQgntAHeb+86
NwZFk5u7en9u7/p0uT1Qsg6M38Y/tpmQMB5y5JcgYD7gIRTBXghZSoN0XHa3JjYB
mdkKcrzFRaIJK3VP+aWZj2DRHYJdJmzPNbuFXoWlwQuyk4duy3uZgBqLzpJa5D6Y
RtezKcLaGz6FOWux/l8lhl3Im1MomJpofv6ynCLzWZx0H6GcChl5wbnZuE935jYv
zvYjVsY8KyQQNN3wojfS6e6xJ9QQx/GQqn2dD29IO1Kg/PKUHL1MDqlxG6BD2p8p
/dg4h1LLnaJSDCa/qHVB1Q7xtszkEOhIngt4S7JSdQKBgQD7IgF4wqVgNIK99M3b
H1CnVir1hJCgmsDWFluInyLE9tSJsD3KeLUFmhlff7j+7qlYa4he4H94E2EvRbN2
02GvB/DvJcgFQP4276h93Jd5xi8IeeNH3xAQf+U7B5gWrycy2DDJ47naBxGFTBtS
U+NPd+6Jhki0cZZdlKvuqveauwKBgQDT3pinHpZAU/WIu/iAzNU381y4K3nb+IKN
/ylh1VwfWySCYUKYdwnnZhxku3rz2MaM6BP89UZfyADQUyib/4VtjqjDVjcEJ+23
hEC9meVrQelasNBXvESkLc8TzeNgQ5Ax4lEaLmUN7rW6K0hcEXP+HpgK34dL1Z8m
HRJ3QioubQKBgQDbTwcZ/mGNck36tR5QjThR6d36KppipsJgM4Yb+VzTxPo2g8Jy
Xpc3HSurSk6z0pfwr3eX41Vt9v6X2bavvklAIR82uTfD3s4iC4iI4Wsm3PuV21uw
GAxfXFFDC461wJ/qtwFkTYqv7BxQ2/XAH6ISdBnL34j+i3NUpdC/zG4OoQKBgFQV
Hi4MxIJpD6OhPcEYCXJ4AY8Rqf3zhRSHdEvgWfwTtsyLftt1Opf7/T647NXDbSDU
pcIZMUGn5TLIHfCzGqfdGvrSx2i90+il8u+MGPFVKiAU1cpL7E3f5DOu3RMCHUK+
14L1cKNz3ekxbjkjsA3k5GBET7R35f5BQWfU+VqNAoGASzE+hC+9ul84kRmRAkOH
OFXcTDvKjjQdBHJ2UCbw2jPhBm0/LDQVmeWYBWrJO1yqsEbnZ0D/a2ReyLRRiW/P
dZgVfgRgC4V5LGEEFPheWZ44uN/kaQYMi9RCAswKniNb39oeU4PZqEAn2xxfkr+S
2w+U3hv0ypr+cSTPJwNyk9c=
-----END PRIVATE KEY-----
PEM;

    protected static $publicKey2 = <<<'PEM'
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAz9dkc/E3JEHBIKg9+/Qx
0k3boOawmW6RiCzjUuYfSl+DXyZAD3fJoNmCM+zkIwkwsCx/6/dbaztH3YvXJBvH
8s26/yYn61JSG4J3mtw2kXTeV6jqXF11ivOTNeQCT/pO5RAPN6Cm+WG24MsbzhNQ
H3H1ouc8Vz1TcrmgUBXcFnFgHPkWnnbHTHyu4uc016xTBkHv1w8bWPl09JR5iRHi
UINDn2H7lSqP5+dLLZe3VLHUQYyUpqBB6htgv2TD3FR8zI/Vda/XRrdIGXkqHztg
W7ZJew0GKqwJjNedh6K9d/d+QyBoqET4FEyUKpGFnz+OOBNKe8j1lK53lwzH4eh7
nwIDAQAB
-----END PUBLIC KEY-----
PEM;

    public function testValidateJwtReturnsTrueForValidToken()
    {
        // configure the TokenService to use publicKey1 and token max age 60s
        Config::modify()->set(TokenService::class, 'public_key', self::$publicKey1);
        Config::modify()->set(TokenService::class, 'token_max_age_seconds', 60);

        $payload = [
            'sub' => '1234567890',
            'name' => 'John Doe',
            'iat' => time(),
        ];

        $jwt = JWT::encode($payload, self::$privateKey1, 'RS256');

        $service = new TokenService();
        $this->assertTrue($service->validateJwt($jwt));
    }

    public function testValidateJwtReturnsTrueForValidEnvironmentToken()
    {
        Environment::setEnv('SILVERGATECLIENT_PUBLIC_KEY', self::$publicKey1);
        Config::modify()->set(TokenService::class, 'token_max_age_seconds', 60);

        $payload = [
            'sub' => '1234567890',
            'name' => 'John Doe',
            'iat' => time(),
        ];

        $jwt = JWT::encode($payload, self::$privateKey1, 'RS256');

        $service = new TokenService();
        $this->assertTrue($service->validateJwt($jwt));
    }

    public function testValidateJwtReturnsFalseForExpiredToken()
    {
        Config::modify()->set(TokenService::class, 'public_key', self::$publicKey1);
        Config::modify()->set(TokenService::class, 'token_max_age_seconds', 1);

        $payload = [
            'sub' => '123',
            'iat' => time() - 3600,
        ];

        $jwt = JWT::encode($payload, self::$privateKey1, 'RS256');

        $service = new TokenService();
        $this->assertFalse($service->validateJwt($jwt));
    }

    public function testValidateJwtReturnsFalseForInvalidSignature()
    {
        // configure TokenService with publicKey1, but sign with privateKey2
        Config::modify()->set(TokenService::class, 'public_key', self::$publicKey1);
        Config::modify()->set(TokenService::class, 'token_max_age_seconds', 60);

        $payload = [
            'sub' => 'abc',
            'iat' => time(),
        ];

        $jwt = JWT::encode($payload, self::$privateKey2, 'RS256');

        $service = new TokenService();
        $this->assertFalse($service->validateJwt($jwt));
    }

    public function testValidateJwtReturnsFalseForMalformedToken()
    {
        Config::modify()->set(TokenService::class, 'public_key', self::$publicKey1);
        Config::modify()->set(TokenService::class, 'token_max_age_seconds', 60);

        $service = new TokenService();
        $this->assertFalse($service->validateJwt('not.a.jwt'));
        $this->assertFalse($service->validateJwt('too.many.parts.in.this.token.string'));
    }
}
