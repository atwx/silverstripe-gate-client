<?php

namespace Atwx\SilverGateClient\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use SilverStripe\Core\Config\Configurable;
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

    protected function getPublicKey()
    {
        return $this->config()->get('public_key');
    }

    public function validateJwt(string $jwt): bool
    {
        try {
            $alg = $this->getJwtAlgorithm($jwt);
            if (!$alg) {
                return false;
            }
            $key = new Key($this->getPublicKey(), $alg);
            $decoded = JWT::decode($jwt, $key);

            $now = time();
            $tokenMaxAge = self::config()->get('token_max_age_seconds');
            if (isset($decoded->iat) && $tokenMaxAge > 0 && ($now - $decoded->iat) > $tokenMaxAge) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getJwtAlgorithm(string $token): ?string
    {
        $tks = explode('.', $token);
        if (count($tks) !== 3) {
            return null;
        }
        list($headb64, $bodyb64, $cryptob64) = $tks;
        $headerRaw = JWT::urlsafeB64Decode($headb64);
        if (null === ($header = JWT::jsonDecode($headerRaw))) {
            return null;
        }
        $alg = $header->alg ?? 'UNKNOWN';
        return array_key_exists($alg, JWT::$supported_algs) ? $alg : null;
    }
}
