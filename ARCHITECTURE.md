# Architecture: sanctum

## Purpose

Laravel Sanctum provides a lightweight API token authentication system and SPA (Single Page Application) authentication. Supports personal access tokens (PAT) for API authentication and cookie-based session authentication for first-party SPAs.

## Directory Structure

```
src/
  Sanctum.php                                   - Static configuration facade; token model override
  Sanctum_Service_Provider.php                  - Laravel service provider; routes, middleware, migrations
  Personal_Access_Token.php                     - Eloquent model for stored API tokens
  New_Access_Token.php                          - Value object returned after token creation (includes plain-text token)
  Transient_Token.php                           - In-memory token for SPA session requests (no DB row)
  Has_Api_Tokens.php                            - Trait added to User model: createToken(), tokens(), etc.
  Guard.php                                     - Laravel auth guard: resolves tokens from Bearer header or session
  Contracts/
    Has_Abilities.php                           - Contract for ability-checking on tokens
    Has_Api_Tokens.php                          - Contract for models that issue tokens
  Events/
    Token_Authenticated.php                     - Fired when a token successfully authenticates a request
  Exceptions/
    Missing_Ability_Exception.php               - Thrown by tokenCan() checks
    Missing_Scope_Exception.php                 - Scope-check variant
  Http/
    Controllers/Csrf_Cookie_Controller.php      - Issues CSRF cookie for SPA auth
    Middleware/
      Authenticate_Session.php                  - Ensures session user matches token user
      Check_Abilities.php / Check_Scopes.php    - Middleware for ability/scope gates
      Ensure_Frontend_Requests_Are_Stateful.php - Adds session middleware for first-party SPAs
  Console/Commands/
    Prune_Expired.php                           - php artisan sanctum:prune-expired — removes old tokens
```

## Key Design Decisions

- **Two auth modes in one package**: PATs use a hashed Bearer token in the `personal_access_tokens` table; SPA auth uses Laravel's built-in session with a CSRF cookie. Both are resolved by the same `Guard`.
- **Plain-text token returned once**: `New_Access_Token` wraps the raw token string (shown once on creation) and the Eloquent model. The DB only stores a SHA-256 hash.
- **Ability system**: Tokens carry a JSON `abilities` array, checked via `$token->can('update-post')`. This is simpler than Laravel Gates — no policy classes needed.
- **Transient token for SPAs**: Session-authenticated requests receive a `Transient_Token` that passes all ability checks, avoiding double-auth overhead.

## Extension Points

- Swap the token model via `Sanctum::usePersonalAccessTokenModel(MyToken::class)`.
- Add custom token resolution logic via `Sanctum::getAccessTokenFromRequestUsing(callable $callback)`.
- Add middleware to the Sanctum middleware group for additional SPA request processing.

## Dependency Flow

```
HTTP Request
  └─> Guard::user()
        └─> resolve token from Bearer header → Personal_Access_Token::findToken()
        └─> OR resolve from session → Transient_Token (SPA)
        └─> verify token hash, expiry, abilities
        └─> fire Token_Authenticated event
```
