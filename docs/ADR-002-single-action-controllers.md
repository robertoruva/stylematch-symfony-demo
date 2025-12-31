# ADR-002: Single Action Controllers

## Status
Accepted - 2025-11-25

## Context
In Laravel, controllers can handle multiple actions (Resource Controllers) or a single action (Single Action Controllers / Invokable Controllers).

The CombinaMejor project follows hexagonal architecture with clearly separated bounded contexts. We needed to decide how to structure the controllers in the presentation layer.

## Decision
Use **Single Action Controllers** with `__invoke()` method for all API endpoints.

Adopted structure:
```
app/Auth/Infrastructure/Http/Controllers/
├── LoginController.php          → __invoke()
├── LogoutController.php         → __invoke()
├── RegisterController.php       → __invoke()
└── User/
    └── MeController.php         → __invoke()
```

## Consequences

### Positive
- **Maximum cohesion:** Each controller has a single responsibility (Single Responsibility Principle)
- **Simplified testing:** One test per controller, easy to name and maintain
- **Clear dependency injection:** Only the necessary dependencies for that action
- **Natural namespacing:** Controllers grouped by functionality (User/, Auth/)
- **Safe refactoring:** Changing one endpoint doesn't affect others
- **Easier code review:** Smaller, focused PRs
- **Hexagonal architecture compatibility:** Controllers as specific adapters

### Negative
- **More files:** One file per action vs one with multiple actions
- **Navigation:** More files to navigate in the IDE
- **Boilerplate:** Repetition of basic structure (namespace, use, class)

### Neutral
- **Route convention:** Using `Route::post('/login', LoginController::class)` instead of `[Controller::class, 'login']`
- **Consistency:** Entire team must follow the same pattern

## Alternatives Considered

### 1. Resource Controllers (Laravel standard)
```php
class AuthController extends Controller
{
    public function login() { }
    public function logout() { }
    public function register() { }
}
```

**Rejected because:**
- Violates Single Responsibility: one controller with multiple reasons to change
- More complex testing: one TestCase with multiple tests
- Unnecessary dependencies: inject everything for all actions
- Less cohesive with bounded contexts

### 2. Hybrid approach (mixing both)
**Rejected because:**
- Inconsistency in the codebase
- Confusion about when to use each pattern
- More difficult code review

## References
- [Laravel Single Action Controllers](https://laravel.com/docs/11.x/controllers#single-action-controllers)
- [Adam Wathan - Curing the common loop](https://adamwathan.me/2016/07/14/curing-the-common-loop/)
- [Uncle Bob - Single Responsibility Principle](https://blog.cleancoder.com/uncle-bob/2014/05/08/SingleReponsibilityPrinciple.html)
- Current structure: `app/Auth/Infrastructure/Http/Controllers/`
