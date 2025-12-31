# ADR-001: PostgreSQL over MySQL

## Status
Accepted - 2025-12-26

## Context
CombinaMejor is a fashion-tech platform that needs to store:
- Structured user and authentication data
- Flexible clothing item metadata (colors, seasons, occasions)
- Outfit combinations with scoring
- AI-based recommendations (future)

Initially, the project used MySQL 8.0, but there was a need to evaluate alternatives to support future product requirements.

## Decision
Migrate from MySQL 8.0 to PostgreSQL 16 Alpine as the primary database.

## Consequences

### Positive
- **Native JSONB:** Allows storing flexible clothing metadata without constant migrations
- **Full-Text Search:** Search for "blue summer shirt" without external dependencies
- **pgvector extension:** Ready for AI embeddings and semantic searches
- **Advanced Window Functions:** Complex queries for outfit recommendations
- **Robust Multi-tenancy:** Native schemas for future tenant separation
- **OLTP Performance:** Better handling of concurrent transactions
- **Complete SQL Standards:** Greater compatibility with ANSI SQL

### Negative
- **Learning curve:** Team has more experience with MySQL
- **Tooling:** Administration tools less popular than MySQL
- **Hosting:** Some shared hosting options don't support Postgres

### Neutral
- **Similar performance:** For typical OLTP loads, both are comparable
- **Mature ecosystem:** Both have active and extensive communities

## Alternatives Considered

### 1. Keep MySQL 8.0
**Rejected because:**
- JSON in MySQL is more limited than JSONB (no efficient indexes)
- Full-text search requires complex configuration
- No native solution for AI embeddings

### 2. Hybrid Architecture (MySQL + MongoDB)
**Rejected because:**
- Increases operational complexity (2 databases)
- Synchronization between systems
- Higher hosting and maintenance costs

### 3. PostgreSQL + MongoDB (CQRS)
**Considered for the future:**
- Postgres for Commands (Write Model)
- MongoDB for Queries (Optimized Read Model)
- Will be implemented if read volume justifies it

## References
- [PostgreSQL 16 Release Notes](https://www.postgresql.org/docs/16/release-16.html)
- [pgvector Extension](https://github.com/pgvector/pgvector)
- [PostgreSQL JSONB](https://www.postgresql.org/docs/current/datatype-json.html)
- Commit: `feat: migrate from MySQL to PostgreSQL with health checks` (2025-12-19)
