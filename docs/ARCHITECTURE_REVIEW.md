General Architecture
Stack: PHP 8.3 + Symfony 6.4 (LTS) + Doctrine ORM 3 + PostgreSQL 16
Main Patterns: Hexagonal Architecture + DDD + CQRS + Partial Event Sourcing
Business Context: Management API for homeowners' associations / condominiums (common expenses, incomes, monthly settlements (slips), gas consumption, resident notifications).

Request/response flow

"pullDomainEvents()"
"HTTP Request"
"Controller\n(Infrastructure)"
"RequestDto\n(Infrastructure)"
"CommandBus\n(Messenger)"
"CommandHandler\n(Application)"
"Domain Aggregate\n(AggregateRoot)"
"Repository\n(Domain Interface)"
"Doctrine Repository\n(Infrastructure)"
"EventBus\n(Messenger)"
"EventStore\n(event_store table)"
"Event Subscribers\n(Notifications, Projections)"
Bounded Contexts (8 contexts)
Account — Accounting accounts, balance, initial balance.

Expense — One-off and recurring expenses, types, attachments (Google Document AI).

Income — Incomes and income types.

Slip — Settlements with a state machine (Symfony Workflow): PENDING → SUBMITTED → PAID.

ResidentUnit — Housing units, ideal fraction (quota share), recipients.

Gas — Consumption readings, price per m³, average per unit.

User — Registration, JWT authentication, avatar, unit linking.

EventStore — Persistence of all Domain Events in the system.

What is working really well (Strengths)
Clean vertical structure: Each context is completely self-contained with its 3 layers (Domain/, Application/, Infrastructure/). Routes are loaded via glob in config/routes.yaml, allowing new contexts to be added without touching the root configuration.

Dependency-free Domain: Aggregates, Value Objects, and repositories (interfaces) live inside Domain/ without any dependencies on Symfony or Doctrine. The domain is pure PHP.

Well-implemented CQRS: Two separate buses (command.bus with the doctrine_transaction middleware, and query.bus without it). Handlers are atomic and highly focused.

Expressive Value Objects: ExpenseTypeDistributionMethod with validated enums, SlipGenerationPolicy with complex temporal rules, ResidentUnitValidator as a Domain Service. The code perfectly reflects the ubiquitous language of the business.

Immutable events: All Domain Events extend abstract readonly class DomainEvent. Immutability is guaranteed by design.

Object Mother pattern in tests: 51 Mother classes for test data generation. Integration tests verify both the HTTP response and that the event was properly persisted in the EventStore — an excellent level of assertion.

Selective and pragmatic Event Sourcing: GetAccountBalanceQueryHandler reconstructs the balance by reading the EventStore directly. This is the right call: full Event Sourcing isn't forced where it doesn't add value.

Issues to Resolve
High Priority
EventStore duality (potential bug):
Two subscribers coexist and write to the same event_store table using different columns:

DomainEventStoreSubscriber → uses ORM, writes event_type / payload

EventStoreSubscriber → uses direct DBAL, writes event_name / body / content_hash
One must be chosen, and the other removed. The md5 hash in the second one suggests there were duplicate entries, a clear sign that both systems are running without coordination.

ApiTestCase drops and recreates the schema on every test:

PHP

// tests/Shared/Infrastructure/PhpUnit/ApiTestCase.php
$schemaTool->dropSchema($metadata);
$schemaTool->createSchema($metadata);
This renders dama/doctrine-test-bundle (which is already installed and configured) useless. Its exact purpose is to avoid this performance hit by using transactional rollbacks. Integration tests are unnecessarily slow right now.

async transport forced to sync:// in production:

YAML

# config/packages/messenger.yaml
async: 'sync://'  # <-- same in production as in development
Mass slip dispatching and PDF generation are being processed synchronously. In production, this can lead to timeouts. A real transport mechanism (Redis, RabbitMQ) should be configured, or the decision should at least be explicitly documented.

Medium Priority
StoredEvent::toDomainEvent() violates the Open/Closed Principle:
The EventStore domain has direct dependencies on classes from other contexts:

PHP

$eventClassMap = [
    IncomeWasEntered::eventName() => IncomeWasEntered::class,
    ExpenseWasEntered::eventName() => ExpenseWasEntered::class,
    GasPriceWasDefined::eventName() => GasPriceWasDefined::class,
    // ...
];
Every new event requires modifying this file. The solution is a configurable registry in the infrastructure layer, injected as a dependency.

Two Controller styles coexisting:

New contexts: Direct injection of the CommandHandler (final readonly class).

User context: Extends ApiController with $commandBus.
The User context should be migrated to the new pattern for consistency.

Minor domain bugs:

ExpenseDueDate::fromDateTime() returns void instead of self — unimplemented method.

Expense::activate(bool $isActive) ignores the parameter and always activates.

EnterExpenseCommandHandler calls save() twice with flush.

Mutable DTOs:
DTO properties are public string $id when they should be public readonly string $id.

Low Priority
Docker without multi-stage build: Xdebug is present in the production image. A multi-stage build would strip development tools from the final image.

Inconsistent event namespace: Most are in Domain/Bus/, but some are in Domain/Event/. This should be unified (preferably to Domain/Event/).

Infection configured but missing script: infection/infection is in require-dev, but there is no composer test:mutation command available to run it.

Summary
The code features a mature and well-thought-out architecture. The separation of concerns is clear, the domain is clean, and the patterns are properly applied. The most urgent issues are the EventStore duality (which might be silently causing incorrect data) and the ApiTestCase setup (which unnecessarily slows down the test suite). The rest are ongoing refactoring inconsistencies, which is completely normal in a living, evolving project.