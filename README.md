# Silverstripe Gate Client

A SilverStripe module that lets you log into a SilverStripe site from a centralized system using signed JWTs and then redirects you into the CMS.

In short: the module validates a signed JSON Web Token (JWT) passed via the URL. On successful validation a configured user (or the default admin) is automatically logged in and redirected to a configured destination URL.

Use the [Silverstripe Gate Manager](https://github.com/atwx/silverstripe-gate-manager) on your admin instance to do so.

## Installation

This package is intended to be installed via Composer. From your project root:

```bash
composer require atwx/silverstripe-gate-client
```

## Configuration

Configure the module via SilverStripe YAML config. Important options:

```yaml
Atwx\SilverGateClient\Services\TokenService:
  public_key: |
    -----BEGIN PUBLIC KEY-----
    ...
    -----END PUBLIC KEY-----

# Login as admin is default
Atwx\SilverGateClient\Services\LoginService:
  # Finds the current default admin or creates one if none exists
  login_as_default_admin: true
  #member_id: 1
  #member_email: xyz@example.com
```

Note: the key must be in PEM format. Tokens should be signed by the central system that issues them with the corresponding private key (for example RS256).

You can also use the `.env` to configure the public key. Make sure to escape newlines using \n:

```env
SILVERGATECLIENT_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----\n...\n-----END PUBLIC KEY-----"
```

More advanced configuration:

```yaml
Atwx\SilverGateClient\Services\TokenService:
  public_key: |
    -----BEGIN PUBLIC KEY-----
    ...
    -----END PUBLIC KEY-----
  # defaults to 60, specifies how old the token is allowed to be before it is invalidated
  token_max_age_seconds: 60

Atwx\SilverGateClient\Services\LoginService:
  member_id: 1
  login_dest: '/custom-url-after-login'
```

## Sudo Mode

By default, SilverStripe's [Sudo Mode](https://docs.silverstripe.org/en/developer_guides/security/sudo_mode/) is not activated after a JWT login. This means users who access sudo-protected CMS areas (e.g. certain security settings) will be prompted for their password — which is not possible for SSO users.

You can opt in to automatically bypassing sudo mode for JWT-authenticated sessions:

**Via `.env`:**

```env
SILVERGATECLIENT_DISABLE_SUDO_MODE=1
```

**Via YAML:**

```yaml
Atwx\SilverGateClient\Services\LoginService:
  disable_sudo_mode: true
```

When enabled, a `jwt-authenticated` flag is set in the session after a successful JWT login. A `SudoModeExtension` on `SudoModeService` detects this flag and marks sudo mode as active for the duration of the session.

**Security considerations:**

- The feature is opt-in and disabled by default.
- The session flag is only set after JWT signature validation and a successful member login.
- The flag is cleared when the user logs out (session is destroyed).
- Unlike normal sudo mode (which times out after 45 minutes), the bypass is active for the entire session. This is intentional for SSO users who cannot enter a password.

## Route / Endpoint

The module registers the route `/_silvergateclient` (see `_config/routes.yml`).

Primary endpoint:

- GET `/_silvergateclient/token/<urlencoded_base64_jwt>`

Example (as used in the tests):

```php
$token = JWT::encode(['iat' => time()], $privateKey, 'RS256');
$b64 = urlencode(base64_encode($token));
// GET /_silvergateclient/token/<$b64>
```