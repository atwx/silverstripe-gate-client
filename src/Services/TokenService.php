<?php

namespace Atwx\SilverGateClient\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;

class TokenService
{
    use Configurable;
    use Injectable;
    use Extensible;

    /**
     * Maximum age of a token in seconds.
     *
     * @config
     */
    private static int $token_max_age_seconds = 60;

    /**
     * Public key for token validation.
     *
     * @config
     */
    private static ?string $public_key = null;

    /**
     * The signing algorithm the token must use. This is fixed server-side and
     * never derived from the token itself, to prevent algorithm-confusion
     * attacks where an attacker crafts the header to select a weaker or
     * symmetric algorithm.
     *
     * @config
     */
    private static string $algorithm = 'RS256';

    protected function getPublicKey()
    {
        return Environment::getEnv('SILVERGATECLIENT_PUBLIC_KEY') ?: $this->config()->get('public_key');
    }

    public function validateJwt(string $jwt): bool
    {
        try {
            $alg = $this->config()->get('algorithm');
            $key = new Key($this->getPublicKey(), $alg);
            $decoded = JWT::decode($jwt, $key);

            $now = time();
            $tokenMaxAge = $this->config()->get('token_max_age_seconds');
            if (isset($decoded->iat) && $tokenMaxAge > 0 && ($now - $decoded->iat) > $tokenMaxAge) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
