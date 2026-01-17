<?php

namespace Atwx\SilverGateClient\Controllers;

use Atwx\SilverGateClient\Services\LoginService;
use Atwx\SilverGateClient\Services\TokenService;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Model\ArrayData;

class LoginController extends Controller
{
    private static string $url_segment = '_silvergateclient';

    private static array $allowed_actions = [
        'token'
    ];

    private static array $url_handlers = [
        'token/$token' => 'token'
    ];

    public function index()
    {
        return $this->httpError(404);
    }

    public function token(HTTPRequest $request)
    {
        $tokenB64 = $request->param('token');
        $token = base64_decode(urldecode($tokenB64 ?? ''), true);
        if (!$token || empty($token)) {
            return $this->httpError(400, 'Token is required or format is invalid.');
        }

        $tokenValid = TokenService::singleton()->validateJwt($token);
        if (!$tokenValid) {
            return $this->httpError(403, 'Token is invalid or expired.');
        }

        $loginMember = LoginService::singleton()->findAndLogInMember();
        if (!$loginMember) {
            return $this->httpError(403, 'No valid user to login was found.');
        }

        return ArrayData::create([
            'Link' => LoginService::singleton()->getRedirectUrl() ?: '/'
        ])->renderWith('Atwx\SilverGateClient\LoginRedirectPage');
    }
}
