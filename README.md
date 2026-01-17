# SilverGate Client

A SilverStripe module that lets you log into a SilverStripe site from a centralized system using signed JWTs and then redirects you into the CMS.

In short: the module validates a signed JSON Web Token (JWT) passed via the URL. On successful validation a configured user (or the default admin) is automatically logged in and redirected to a configured destination URL.

Use the [SilverGate Manager](https://github.com/atwx/silvergate-manager) on your admin instance to do so.

## Installation

This package is intended to be installed via Composer. From your project root:

```bash
composer require atwx/silvergate-client
```

## Configuration

Configure the module via SilverStripe YAML config. Important options:

```yaml
Atwx\SilverGateClient\Services\TokenService:
  public_key: |
    -----BEGIN PUBLIC KEY-----
    ...
    -----END PUBLIC KEY-----

# One has to be specified
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