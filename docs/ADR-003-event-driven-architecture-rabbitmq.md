# ADR-003: Event-Driven Architecture with RabbitMQ

## Status
Accepted - 2025-11-26

## Context
CombinaMejor needs to execute asynchronous tasks such as:
- Sending welcome emails after registration
- Processing clothing item images
- Generating outfit recommendations
- Push notifications
- Synchronization with external services

These tasks should not block HTTP responses and require delivery guarantees, retries, and ordered processing.

## Decision
Implement **Event-Driven Architecture** using:
- **Domain Events** in the domain layer
- **RabbitMQ** as message broker
- **php-amqplib** as PHP client
- **Dedicated consumers** as Artisan commands

### Implemented Architecture:
```
┌─────────────────────────────────────┐
│   RegisterUserHandler (Application) │
│   1. Saves user to Postgres         │
│   2. Publishes UserRegisteredMessage│
└──────────────┬──────────────────────┘
               │
               ↓
      ┌────────────────┐
      │   RabbitMQ     │
      │ Queue: user_   │
      │   registered   │
      └────────┬───────┘
               │
               ↓
┌──────────────────────────────────────┐
│  Consumer (Background Process)       │
│  ConsumeUserRegisteredMessages       │
│  - Sends welcome email               │
│  - ACK/NACK based on result          │
└──────────────────────────────────────┘
```

## Consequences

### Positive
- **Decoupling:** Domain doesn't depend on email/notification implementation
- **Scalability:** Multiple consumers processing in parallel
- **Resilience:** Automatic retries with dead-letter queues
- **Traceability:** Logs of each processed message
- **Testing:** Easy to mock MessageBus in unit tests
- **Async by default:** Fast HTTP responses, heavy work in background
- **Audit trail:** System event history
- **Eventual consistency:** Ready for distributed architecture

### Negative
- **Operational complexity:** One more service to maintain (RabbitMQ)
- **Harder debugging:** Async errors are less obvious
- **Eventual consistency:** Not strong consistency
- **Infrastructure:** Requires RabbitMQ in all environments
- **Learning curve:** Team must understand async messaging

### Neutral
- **Latency:** Async processing introduces delay (acceptable for our use case)
- **Monitoring:** Requires specific tools (RabbitMQ Management UI)

## Alternatives Considered

### 1. Laravel Queues with database
**Rejected because:**
- Doesn't scale well with high volume
- Database becomes a bottleneck
- Less flexible than RabbitMQ for complex routing
- Lacks advanced features (dead-letter, priority queues)

### 2. Redis Pub/Sub
**Rejected because:**
- Doesn't guarantee delivery (fire-and-forget)
- No message persistence
- No automatic retries
- Better for real-time notifications, not critical tasks

### 3. AWS SQS / Google Cloud Pub/Sub
**Considered for the future:**
- Advantage: Serverless, no operations
- Disadvantage: Vendor lock-in
- Disadvantage: Variable costs based on volume
- Will be evaluated when scaling

### 4. Apache Kafka
**Rejected because:**
- Overkill for our current volume
- Much more complex to operate
- Designed for massive streaming, not task queues
- Heavier infrastructure

## Implementation

### Message Bus Interface (Port)
```php
interface MessageBusInterface
{
    public function publish(MessageInterface $message, string $queue): void;
}
```

### RabbitMQ Publisher (Adapter)
```php
class RabbitMQPublisher implements MessageBusInterface
{
    public function publish(MessageInterface $message, string $queue): void
    {
        // Publishes message with persistence and ACK
    }
}
```

### Consumer Pattern
```bash
php artisan rabbitmq:consume-user-registered
```

With graceful shutdown (SIGTERM/SIGINT) for deployments without message loss.

## Delivery Guarantees

1. **Persistence:** Messages survive RabbitMQ restarts
2. **Manual ACK:** Consumer confirms successful processing
3. **NACK with requeue:** Automatic retries on error
4. **Dead-letter queue:** Messages that fail multiple times go to DLQ
5. **Idempotency:** Handlers must be idempotent

## References
- [RabbitMQ Documentation](https://www.rabbitmq.com/documentation.html)
- [php-amqplib GitHub](https://github.com/php-amqplib/php-amqplib)
- [Martin Fowler - Event-Driven Architecture](https://martinfowler.com/articles/201701-event-driven.html)
- [Implementing Domain Events](https://learn.microsoft.com/en-us/dotnet/architecture/microservices/microservice-ddd-cqrs-patterns/domain-events-design-implementation)
- Code: `app/Auth/Infrastructure/Messaging/RabbitMQ/`
- Config: `config/rabbitmq.php`
