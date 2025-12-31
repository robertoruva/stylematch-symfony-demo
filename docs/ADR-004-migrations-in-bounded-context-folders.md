# ADR-004: Migrations in Bounded Context Folders

## Status
Accepted - 2025-11-25

## Context
Laravel by default places all migrations in `database/migrations/`, which works well for small monolithic applications. However, CombinaMejor follows Domain-Driven Design with multiple Bounded Contexts (Auth, Wardrobe, Recommendations, etc.).

We needed to decide where to place migrations for each bounded context to maintain cohesion and facilitate future extraction into microservices.

## Decision
Place migrations inside each bounded context, in the Infrastructure layer:
```
app/
└── Auth/
    ├── Domain/
    ├── Application/
    └── Infrastructure/
        ├── Database/
        │   └── Migrations/
        │       └── 2025_11_25_104351_create_users_table.php
        ├── Http/
        └── Persistence/
```

### Required configuration:
```php
// app/Auth/Infrastructure/Database/AuthMigrationsServiceProvider.php
class AuthMigrationsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(
            __DIR__ . '/Migrations'
        );
    }
}
```

## Consequences

### Positive
- **Maximum cohesion:** All Auth code in one place
- **Portability:** Easy to extract Auth into a Composer package
- **Separation of concerns:** Each bounded context manages its schema
- **Team independence:** Teams can work without migration conflicts
- **Clarity:** Obvious which tables belong to which context
- **Microservices ready:** Prepared to split into independent services
- **Namespace isolation:** `Auth` has its own complete namespace

### Negative
- **Not Laravel convention:** New developers will expect `database/migrations/`
- **Tooling:** Some IDEs/plugins assume the standard location
- **Scarce documentation:** Few examples of this structure in the community
- **Additional Service Provider:** Need to register each MigrationServiceProvider

### Neutral
- **Artisan command:** `php artisan migrate` still works the same
- **Performance:** No impact, Laravel loads all migrations
- **Testing:** Migrations run normally in tests

## Alternatives Considered

### 1. Laravel standard (`database/migrations/`)
```
database/
└── migrations/
    ├── 2025_11_25_create_users_table.php
    ├── 2025_11_26_create_wardrobe_items_table.php
    └── 2025_11_27_create_recommendations_table.php
```

**Rejected because:**
- Violates bounded context cohesion
- Hard to identify which migration belongs to which context
- Merge conflicts when multiple teams work
- Not portable: impossible to extract a bounded context

### 2. Folders per context in `database/migrations/`
```
database/
└── migrations/
    ├── auth/
    ├── wardrobe/
    └── recommendations/
```

**Rejected because:**
- Still centralizes migrations outside bounded contexts
- Cannot extract bounded contexts as packages
- Half measure: doesn't solve the portability problem

### 3. Migrations in Domain layer
```
app/Auth/Domain/Migrations/
```

**Rejected because:**
- Violates hexagonal architecture: Domain shouldn't know about database
- SQL schema is an implementation detail (Infrastructure)
- Domain must be persistence agnostic

## Implementation

### 1. Create the ServiceProvider:
```php
namespace App\Auth\Infrastructure\Database;

use Illuminate\Support\ServiceProvider;

class AuthMigrationsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Migrations');
    }
}
```

### 2. Register in `config/app.php`:
```php
'providers' => [
    // ...
    App\Auth\Infrastructure\Database\AuthMigrationsServiceProvider::class,
],
```

### 3. Create migrations:
```bash
php artisan make:migration create_users_table
# Manually move to app/Auth/Infrastructure/Database/Migrations/
```

## Future Benefits

### Extraction to Composer package:
```json
{
  "name": "combinamejor/auth-package",
  "autoload": {
    "psr-4": {
      "CombinaMejor\\Auth\\": "src/"
    }
  }
}
```

All Auth code (including migrations) can be moved to a reusable package.

### Microservices:
If in the future Auth becomes an independent microservice:
- Migrations go with the service
- Dedicated database for Auth
- No coupling with other contexts

## Accepted Trade-offs

1. **Convention vs Cohesion:** We prioritize cohesion over Laravel convention
2. **Initial Complexity vs Scalability:** More initial setup, but better long-term architecture
3. **Onboarding:** New developers will need extra documentation

## References
- [Domain-Driven Design](https://www.domainlanguage.com/ddd/)
- [Hexagonal Architecture](https://alistair.cockburn.us/hexagonal-architecture/)
- [Laravel Package Development](https://laravel.com/docs/11.x/packages)
- [Modular Monolith](https://www.kamilgrzybek.com/blog/posts/modular-monolith-primer)
- Code: `app/Auth/Infrastructure/Database/Migrations/`
- Provider: `app/Auth/Infrastructure/Database/AuthMigrationsServiceProvider.php`
