# Architecture Decision Records (ADRs)

This directory contains significant architectural decisions made in the CombinaMejor project.

## What is an ADR?

An Architecture Decision Record (ADR) is a document that captures an important architectural decision along with its context and consequences.

## Format

We follow the template proposed by Michael Nygard:
- **Status:** Accepted, Proposed, Rejected, Deprecated
- **Context:** Situation that led us to the decision
- **Decision:** What we decided to do
- **Consequences:** Positive, negative, and neutral trade-offs
- **Alternatives:** Options we considered and why we discarded them

## ADR Index

| ID | Title | Status | Date |
|----|-------|--------|------|
| [ADR-001](ADR-001-postgresql-over-mysql.md) | PostgreSQL over MySQL | Accepted | 2025-12-26 |
| [ADR-002](ADR-002-single-action-controllers.md) | Single Action Controllers | Accepted | 2025-11-25 |
| [ADR-003](ADR-003-event-driven-architecture-rabbitmq.md) | Event-Driven Architecture with RabbitMQ | Accepted | 2025-11-26 |
| [ADR-004](ADR-004-migrations-in-bounded-context-folders.md) | Migrations in Bounded Context Folders | Accepted | 2025-11-25 |

## Creating a New ADR

1. Copy the template:
```bash
cp ADR-000-template.md ADR-XXX-descriptive-title.md
```

2. Complete all sections

3. Update this README with the new ADR

4. Commit with descriptive message:
```bash
git commit -m "docs: add ADR-XXX about [decision]"
```

## References

- [ADR GitHub Organization](https://adr.github.io/)
- [Michael Nygard's ADR](https://cognitect.com/blog/2011/11/15/documenting-architecture-decisions)
- [Joel Parker Henderson's ADR tools](https://github.com/joelparkerhenderson/architecture-decision-record)
