# StyleMatch - Symfony Demo

> Fashion-tech platform backend API showcasing Domain-Driven Design, Hexagonal Architecture, and CQRS patterns

[![PHP](https://img.shields.io/badge/PHP-8.2-blue)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/Symfony-7.1-black)](https://symfony.com/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-blue)](https://www.postgresql.org/)
[![Docker](https://img.shields.io/badge/Docker-Ready-blue)](https://www.docker.com/)
[![Architecture](https://img.shields.io/badge/Architecture-DDD%20%2B%20Hexagonal-orange)](https://github.com/robertoruva/combinamejor-symfony)

---

## 🎯 About This Project
> This repository is intended primarily as a **code and architecture showcase**.
> Running the project locally is optional and not required for review.

CombinaMejor is a fashion-tech platform API that helps users learn to combine clothing effectively. This project serves as a **comprehensive showcase** of modern PHP architecture patterns and best practices.

**This is a learning/portfolio project demonstrating:**
- ✅ Domain-Driven Design with Bounded Contexts
- ✅ Hexagonal Architecture (Ports & Adapters)
- ✅ CQRS (Command Query Responsibility Segregation)
- ✅ Event-Driven Architecture with RabbitMQ
- ✅ Multi-tenancy ready design
- ✅ Production-grade testing strategy
- ✅ Docker multi-stage builds (dev + prod)
- ✅ Designed with CI/CD in mind

---

## 🏗️ Architecture

### Bounded Contexts
```
Auth          → User authentication and authorization
Wardrobe      → Clothing items management (planned)
Outfits       → Clothing combinations (planned)
Recommendations → AI-powered suggestions (planned)
```

### Technology Stack

**Backend:**
- PHP 8.2
- Symfony 7.x
- PostgreSQL 16 (JSONB support, pgvector ready)
- Redis (caching, sessions)
- RabbitMQ (async event processing)

**Architecture Patterns:**
- Domain-Driven Design (DDD)
- Hexagonal Architecture (Ports & Adapters)
- CQRS (Command Query Responsibility Segregation)
- Event-Driven Architecture
- Repository Pattern
- Single Action Controllers

**Infrastructure:**
- Docker multi-stage builds
- Automated health checks
- Development & Production configurations

---

## 📁 Project Structure
```
src/
└── Auth/                          # Bounded Context
    ├── Domain/                    # Business Logic (framework-agnostic)
    │   ├── Entity/
    │   │   └── User.php
    │   ├── ValueObject/
    │   │   ├── Email.php
    │   │   ├── UserId.php
    │   │   └── PasswordHash.php
    │   ├── Repository/
    │   │   └── UserRepositoryInterface.php  # Port
    │   └── Exception/
    │
    ├── Application/               # Use Cases (CQRS)
    │   ├── Command/
    │   │   └── RegisterUser/
    │   │       ├── RegisterUserCommand.php
    │   │       └── RegisterUserHandler.php
    │   └── Query/
    │       └── GetUserById/
    │
    └── Infrastructure/            # Framework & External Services
        ├── Persistence/
        │   └── Doctrine/
        │       └── DoctrineUserRepository.php  # Adapter
        ├── Http/
        │   └── Controller/
        └── Messaging/
            └── RabbitMQ/
```

---

## 🚀 Getting Started

### Prerequisites
- Docker & Docker Compose
- Make (optional)

### Quick Start
```bash
# Clone repository
git clone https://github.com/robertoruva/stylematch-symfony-demo.git
cd stylematch-symfony-demo

# Copy environment file
cp .env.local .env
# Edit .env with your configuration

# Start services
docker compose up -d

# Verify installation
curl http://localhost:8000/api/health
```

**Expected response:**
```json
{
  "status": "ok",
  "framework": "Symfony 7.1",
  "architecture": "DDD + Hexagonal + CQRS",
  "timestamp": "2024-12-30T12:00:00+00:00"
}
```

**Security Note:**
Never commit `.env` files with real credentials to version control.

---

## 🧪 Testing
```bash
# Run all tests
docker compose exec symfony php bin/phpunit

# Run specific suite
docker compose exec symfony php bin/phpunit --testsuite=Unit

# With coverage
docker compose exec symfony php bin/phpunit --coverage-html coverage
```

**Test Structure:**
```
tests/
├── Unit/              # Domain logic (no framework dependencies)
├── Integration/       # Repository implementations
└── Feature/          # HTTP endpoints (full stack)
```

---

## Pre-Push Hook in Action

The hook runs automatically on every push:
```bash
🚀 Running Symfony quality checks before push...
🧹 Checking code style with PHP CS Fixer...
✅ Code style is clean.
🔍 Running PHPStan static analysis...
✅ Static analysis passed.
🧪 Running tests...
✅ All tests passed.
🔒 Checking for security vulnerabilities...
🎉 All checks passed. Proceeding with push...
```

**Benefits:**
- ✅ Prevents bad code from reaching repository
- ✅ Auto-fixes formatting issues
- ✅ Ensures tests always pass
- ✅ Maintains code quality standards

---

## 📚 Architecture Decisions (ADRs)

This project documents architectural decisions in Architecture Decision Records:

- [ADR-001: PostgreSQL over MySQL](docs/ADR-001-postgresql-over-mysql.md)
- [ADR-002: Single Action Controllers](docs/ADR-002-single-action-controllers.md)
- [ADR-003: Event-Driven Architecture with RabbitMQ](docs/ADR-003-event-driven-architecture-rabbitmq.md)
- [ADR-004: Migrations in Bounded Context Folders](docs/ADR-004-migrations-in-bounded-context-folders.md)

See [docs/README.md](docs/) for the complete ADR index and how to create new ones.

---

## 🔐 Security Features

- Password hashing with Argon2id
- JWT token authentication
- CORS configuration
- Rate limiting
- Generic error messages (prevents email enumeration)
- Input validation with Symfony Validator

---

## 🐳 Docker Setup

### Development
```bash
docker compose up -d
```

**Services:**
- Symfony API (port 8000)
- PostgreSQL 16 (port 5432)
- Redis (port 6379)
- RabbitMQ (ports 5672, 15672)
- Swagger UI (port 8082)

---

## 🎓 Learning Outcomes

This project demonstrates:

**✅ Domain-Driven Design:**
- Bounded Contexts with clear boundaries
- Rich Domain Models (Entities, Value Objects, Aggregates)
- Domain Events for cross-context communication
- Ubiquitous Language in code

**✅ Hexagonal Architecture:**
- Domain layer independent of frameworks
- Ports (interfaces) and Adapters (implementations)
- Easy to swap infrastructure (Doctrine ↔ other ORMs)

**✅ CQRS:**
- Separate Command and Query models
- Optimized read models with Redis caching
- Scalable architecture for high-read workloads

**✅ Event-Driven Architecture:**
- Async processing with RabbitMQ
- Loosely coupled bounded contexts
- Scalable message consumers

---

## 🔗 Related Projects

- [CombinaMejor Laravel](private for now) - Same architecture, Laravel 
- [CombinaMejor Frontend](private for now / coming soon) - React + TypeScript

---

## 📖 API Documentation

### Available Endpoints

**Health Check:**
```
GET /api/health
```

**Authentication (Coming Soon):**
```
POST /api/auth/register
POST /api/auth/login
POST /api/auth/logout
GET  /api/auth/me
```

Full API documentation available at: http://localhost:8082 (Swagger UI)

---

## 🤝 Contributing

This is a learning/portfolio project, but feedback and suggestions are welcome!

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'feat: add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 👤 Author

**Roberto Ruiz Vazquez**

- LinkedIn: [robertoruizvazquez](https://linkedin.com/in/robertoruizvazquez)
- GitHub: [@robertoruva](https://github.com/robertoruva)
- Email: roberruizvazquez@gmail.com

---

## 🙏 Acknowledgments

- Inspired by best practices from the DDD community
- Architecture patterns from Eric Evans, Vaughn Vernon, and Martin Fowler
- Symfony documentation and community

---

*Built with ❤️ as a showcase of modern PHP architecture patterns*
