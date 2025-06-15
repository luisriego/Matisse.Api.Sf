# PHP Project Guidelines

## Introduction

These guidelines are established to foster a cohesive, consistent, and high-quality codebase. Their primary purpose is to improve:
- **Readability**: Making code easier to understand for all team members.
- **Consistency**: Ensuring a uniform approach to coding style, design patterns, and project structure.
- **Maintainability**: Simplifying the process of modifying existing code, fixing bugs, and adding new features.
- **Collaboration**: Providing a common framework that helps developers work together more effectively.
- **Quality**: Reducing errors and improving the overall robustness and reliability of the application.

These guidelines are intended to be a living document. As our project evolves, so too will these standards. Suggestions for improvements or changes are welcome and should be discussed with the team (see section 'Updating these Guidelines' for more details). Adherence to these guidelines is expected from all contributors to ensure the long-term health and success of the project.

## Project Structure

```
src/
├── Context/
│   └── {BoundedContextName}/      # e.g., Account, User, Product
│       ├── Application/           # Application services, use cases (Commands/Queries)
│       │   ├── Command/           # Use cases that change state
│       │   │   └── {ActionName}/
│       │   │       ├── {ActionName}Command.php
│       │   │       └── {ActionName}CommandHandler.php
│       │   ├── Query/             # Use cases that read state
│       │   │   └── {ActionName}/
│       │   │       ├── {ActionName}Query.php
│       │   │       └── {ActionName}QueryHandler.php
│       │   ├── DTO/               # Data Transfer Objects (optional, for complex data to/from use cases)
│       │   └── EventListener/     # Application-specific event listeners
│       ├── Domain/                # Core business logic, entities, value objects, domain events
│       │   ├── Model/             # Entities, Aggregates, Value Objects
│       │   │   ├── {AggregateRootName}.php
│       │   │   ├── {EntityName}.php
│       │   │   └── {ValueObjectName}.php
│       │   ├── Event/             # Domain Events
│       │   │   └── {EventName}.php
│       │   ├── Exception/         # Domain-specific exceptions
│       │   │   └── {SpecificExceptionName}.php
│       │   ├── Repository/        # Repository interfaces
│       │   │   └── {AggregateRootName}Repository.php
│       │   └── Service/           # Domain service interfaces (if any)
│       └── Infrastructure/        # Adapters to external systems, implementations of domain interfaces
│           ├── Persistence/       # Repository implementations (e.g., Doctrine ORM)
│           │   └── Doctrine/
│           │       └── Doctrine{AggregateRootName}Repository.php
│           ├── Framework/         # Symfony specific (Controllers (if not APIP resources), console commands)
│           │   └── Symfony/
│           │       ├── Controller/
│           │       └── Command/
│           ├── Service/           # External service clients, other infrastructure concerns
│           └── DependencyInjection/ # For complex DI wiring if needed
├── Shared/                        # Code shared between multiple Bounded Contexts
│   ├── Domain/
│   │   └── ValueObject/           # Common value objects like Uuid, Email, etc.
│   └── Infrastructure/
│       └── Bus/                   # Shared Command/Query/Event bus implementations
tests/
├── Context/
│   └── {BoundedContextName}/
│       ├── Application/
│       │   ├── Command/
│       │   │   └── {ActionName}/{ActionName}CommandHandlerTest.php
│       │   └── Query/
│       │       └── {ActionName}/{ActionName}QueryHandlerTest.php
│       ├── Domain/
│       │   ├── Model/
│       │   │   ├── {AggregateRootName}Test.php
│       │   │   └── {ValueObjectName}Test.php
│       │   └── Mother/            # Test data generators (Mothers)
│       │       ├── {AggregateRootName}Mother.php
│       │       └── {ValueObjectName}Mother.php
│       └── Infrastructure/
│           └── Persistence/
│               └── Doctrine{AggregateRootName}RepositoryTest.php # Integration tests
├── Shared/
│   └── Domain/
│       └── ValueObject/{ValueObjectName}Test.php
└── bootstrap.php                  # PHPUnit bootstrap file
```

**Key Directories in `src/`**:

*   **`Context/{BoundedContextName}/`**: Each directory under `Context/` represents a Bounded Context from Domain-Driven Design. It encapsulates a specific part of the application's domain.
    *   **`Application/`**: Contains the application logic that orchestrates domain objects.
        *   **`Command/` & `Query/`**: Implements CQRS. Commands are operations that change state, Queries retrieve data. Each use case typically has its own subdirectory.
        *   **`DTO/`**: Data Transfer Objects can be used for encapsulating request/response data for application services if simple scalars are not enough.
        *   **`EventListener/`**: Listeners for application-level events.
    *   **`Domain/`**: This is the heart of the Bounded Context, containing the business logic. It should be independent of infrastructure concerns.
        *   **`Model/`**: Contains Entities, Value Objects, and Aggregate Roots.
        *   **`Event/`**: Contains Domain Event classes.
        *   **`Exception/`**: Custom exceptions specific to the domain logic.
        *   **`Repository/`**: Interfaces for data persistence, defining contracts for how domain objects are retrieved and stored.
        *   **`Service/`**: Domain Services that encapsulate domain logic not naturally fitting within an Entity or Value Object.
    *   **`Infrastructure/`**: Contains implementations of interfaces defined in the Domain (like Repositories) or Application layers. It adapts requests from the outside world to the Application and Domain layers.
        *   **`Persistence/`**: Repository implementations (e.g., using Doctrine ORM).
        *   **`Framework/`**: Code specific to the web framework (e.g., Symfony Controllers, Console Commands not directly tied to API Platform resources).
        *   **`Service/`**: Clients for external services, message queue producers/consumers, etc.
        *   **`DependencyInjection/`**: Symfony service definitions specific to this context if not handled in `config/services.yaml`.
*   **`Shared/`**: Contains code that is genuinely shared and reusable across multiple Bounded Contexts. Be cautious about adding code here; prefer duplication over incorrect coupling if unsure.
    *   **`Domain/`**: Shared domain concepts, typically generic Value Objects (e.g., `Uuid`, `Money`, `EmailAddress`) or domain event base classes.
    *   **`Infrastructure/`**: Shared infrastructure components, like base test classes or shared message bus configurations.

**Other Key Project Directories**:

*   **`bin/`**: Executable files, like `console` (Symfony CLI) and `phpunit`.
*   **`config/`**: Application configuration files (routes, services, packages).
*   **`docker/`**: Docker-related files for local development environment.
*   **`migrations/`**: Database schema migration files (Doctrine Migrations).
*   **`public/`**: Web server's document root, containing the front controller (`index.php`) and static assets.
*   **`tests/`**: Contains all automated tests. The structure within `tests/Context/` should mirror `src/Context/` to make it easy to find tests for specific code.
    *   **`Mother/`**: Test Object Mothers for creating complex test fixtures.
*   **`tools/`**: Contains project-specific development tools, like `php-cs-fixer` local installation.
*   **`var/`**: Temporary files like cache, logs (typically not versioned).
*   **`vendor/`**: Composer dependencies (not versioned).

The goal is to maintain a clear separation of concerns, making the codebase easier to navigate, understand, and maintain.

## Coding Standards

### Naming Conventions
- **Classes**: PascalCase (`FindAccountQueryHandler`)
- **Interfaces**: PascalCase (`AccountRepository`)
- **Traits**: `PascalCase`, typically ending with `Trait` (e.g., `TimestampableTrait`, `SoftDeletableTrait`).
- **Abstract Classes**: `PascalCase`, typically starting with `Abstract` (e.g., `AbstractController`, `AbstractRepository`).
- **Test classes**: Same as class name + `Test` suffix
- **Test methods**: camelCase with `test` prefix (`testFindAccount`)
- **Object Mothers**: Entity name + `Mother` suffix (`AccountMother`)
- **Properties (private/protected)**: `camelCase` (e.g., `$customerName`).
- **Methods (private/protected)**: `camelCase` (e.g., `calculateTotal()`).
- **Constants**: `UPPER_CASE_WITH_UNDERSCORES` (e.g., `MAX_LOGIN_ATTEMPTS`).

### Formatting and Style
Our project uses `php-cs-fixer` to automate adherence to a specific code style based on PSR-12 and Symfony standards, with customizations defined in `.php-cs-fixer.dist.php`.

*   **Mandatory Compliance**: All PHP code MUST be compliant with these rules. Before committing any changes, run the fixer:
    ```bash
    ./tools/php-cs-fixer/vendor/bin/php-cs-fixer fix src tests
    ```
    And verify with a dry run:
    ```bash
    ./tools/php-cs-fixer/vendor/bin/php-cs-fixer fix src tests --dry-run
    ```
*   **Strict Types**: All PHP files MUST start with `declare(strict_types=1);` immediately after the opening `<?php` tag. This is enforced by `php-cs-fixer`.
*   **`final` Keyword**:
    *   Classes SHOULD be declared `final` by default, unless they are explicitly designed for inheritance (e.g., abstract classes or specific framework extension points). This promotes composition over inheritance.
    *   Public and protected methods in non-`final` classes SHOULD be declared `final` if they are not intended to be overridden by child classes.
*   **Visibility**: Strive for the most restrictive visibility: `private` by default for properties and methods. Use `protected` only if inheritance is necessary and planned. `public` is for the class's external API.
*   **Type Declarations**: All class properties, method parameters, and return types MUST have type declarations. (This was an "Additional Recommendation" before, now it's a standard).
*   **Readability**:
    *   Keep lines to a reasonable length (e.g., 120 characters). `php-cs-fixer` helps, but be mindful.
    *   Use blank lines to group related blocks of code.
    *   Avoid overly complex expressions or deeply nested control structures. Refactor into smaller methods or functions if necessary.

### Comments and Documentation (PHPDoc)
Clear and consistent documentation is crucial for maintainability. PHPDoc blocks are required for all reusable parts of the codebase.

*   **Required PHPDocs**: PHPDoc blocks ARE REQUIRED for:
    *   All `class` and `interface` definitions (brief description of purpose).
    *   All `public` and `protected` class properties.
    *   All `public` and `protected` class methods.
    *   All `public` constants.
*   **Method PHPDoc Content**: For methods, PHPDocs MUST include:
    *   A concise summary of what the method does.
    *   `@param Type $variableName Description` for each parameter.
    *   `@return Type Description` for the return value (use `void` if no return).
    *   `@throws ExceptionType Description` for any exceptions that the method might directly throw or not catch.
    *   Example:
        ```php
        /**
         * Processes a payment for an order.
         *
         * This method attempts to charge the customer's primary payment method
         * associated with the given order.
         *
         * @param OrderId $orderId The ID of the order to process payment for.
         * @param Money $amount The amount to charge.
         * @return Payment The resulting payment object.
         * @throws PaymentProcessingException if the payment gateway rejects the charge.
         * @throws OrderNotFoundException if the order cannot be found.
         */
        public function processPayment(OrderId $orderId, Money $amount): Payment;
        ```
*   **Property PHPDoc Content**: For properties, PHPDocs MUST include:
    *   `@var Type Description` (e.g., `@var string The user's first name.`). If the property type is complex (e.g., an array of objects), specify it (e.g., `@var User[]`).
*   **Clarity over Redundancy**: Descriptions should be meaningful and not just rephrase the code. If a method name like `getUserById(UserId $id): User` is self-explanatory, the description can be brief but still present.
*   **Inline Comments**: Use inline comments (`//`) sparingly. They should explain *why* something is done in a particular way if it's not obvious, or to mark complex sections or temporary workarounds. Avoid comments that merely state what the code is doing if the code itself is clear.
    ```php
    // TODO: Refactor this section once the new UserProfile module is stable.
    // This is a workaround for legacy system compatibility.
    ```
*   **`php-cs-fixer`**: Our `php-cs-fixer` configuration helps with the formatting and alignment of PHPDoc blocks, but ensuring accurate and complete content is a developer responsibility.

## CQRS (Command Query Responsibility Segregation)

CQRS is an architectural pattern that separates operations that read data (Queries) from operations that write data or perform actions (Commands). This separation helps to simplify complexity, improve scalability, and allow for optimized data models for reading and writing.

Our project leverages CQRS, primarily using Symfony Messenger as the bus implementation.

### Commands

Commands represent an intent to change the state of the application or perform an action.

*   **Purpose**: To encapsulate all information required to perform an action. They are imperative messages.
*   **Naming**: Use a clear `VerbNounCommand.php` convention (e.g., `CreateAccountCommand.php`, `SubmitOrderCommand.php`, `DisableUserCommand.php`).
*   **Structure**:
    *   Commands SHOULD be simple, immutable Data Transfer Objects (DTOs).
    *   Properties SHOULD be `private final` and initialized via the constructor. Only getters should be exposed.
    *   They carry data but contain no behavior.
    *   Example:
        ```php
        // src/Context/Account/Application/Command/CreateAccount/CreateAccountCommand.php
        declare(strict_types=1);

        namespace App\Context\Account\Application\Command\CreateAccount;

        final class CreateAccountCommand
        {
            public function __construct(
                public readonly string $accountId, // Consider using a Value Object like AccountId
                public readonly string $email,
                public readonly string $initialBalance // Consider using a Money Value Object
            ) {
            }
        }
        ```
*   **Return Value**: Commands SHOULD NOT return data. Their execution is typically asynchronous or, if synchronous, success is indicated by lack of an exception. If the ID of a newly created resource is needed, it can be:
    *   Pre-generated by the client (e.g., UUIDs).
    *   Passed within the command if generated by the client.
    *   If generated server-side and absolutely required immediately, the handler *may* (exceptionally) return it, or a separate query should be issued.
*   **Location**: `src/Context/{BoundedContext}/Application/Command/{UseCaseName}/`

### Command Handlers

Command Handlers are responsible for executing Commands.

*   **Purpose**: To orchestrate the necessary steps to fulfill the intent of a Command. They contain the application logic.
*   **Naming**: `VerbNounCommandHandler.php`, corresponding to the Command (e.g., `CreateAccountCommandHandler.php`).
*   **Structure**:
    *   MUST have a single public method to handle the command, typically `__invoke({CommandName} $command)`. This allows the handler to be directly invokable by the Symfony Messenger bus.
    *   Dependencies (Repositories, Domain Services, etc.) ARE INJECTED via the constructor.
    *   The handler retrieves domain entities (Aggregates), invokes methods on them to perform business logic, and uses repositories to persist state changes.
    *   Example:
        ```php
        // src/Context/Account/Application/Command/CreateAccount/CreateAccountCommandHandler.php
        declare(strict_types=1);

        namespace App\Context\Account\Application\Command\CreateAccount;

        use App\Context\Account\Domain\Model\Account;
        use App\Context\Account\Domain\Model\AccountId;
        use App\Context\Account\Domain\Repository\AccountRepositoryInterface;

        final class CreateAccountCommandHandler
        {
            public function __construct(private readonly AccountRepositoryInterface $accountRepository)
            {
            }

            public function __invoke(CreateAccountCommand $command): void
            {
                $accountId = AccountId::fromString($command->accountId);
                // Domain validation for email format could be in a Value Object or Entity factory
                $account = Account::create($accountId, $command->email, $command->initialBalance);

                $this->accountRepository->save($account);

                // Dispatch Domain Events if any (e.g., $account->pullDomainEvents())
            }
        }
        ```
*   **Idempotency**: For critical operations, consider designing handlers to be idempotent (i.e., processing the same command multiple times has the same effect as processing it once). This might involve checking if the operation has already been performed.
*   **Validation**:
    *   Basic input validation (required fields, formats) SHOULD occur before command creation (e.g., in API Platform Data Transformers or Symfony Controllers) or at the very beginning of the command handler.
    *   Business rule validation IS THE RESPONSIBILITY of the Domain Model (Entities, Aggregates, Value Objects).
*   **Location**: Same directory as the corresponding Command.

### Queries

Queries represent a request for information. They read the state of the application without changing it.

*   **Purpose**: To retrieve data. They are descriptive messages.
*   **Naming**: Use conventions like `GetNoun[Details]Query.php`, `FindNounsByCriteriaQuery.php` (e.g., `GetAccountDetailsQuery.php`, `FindUsersByRoleQuery.php`).
*   **Structure**:
    *   Queries SHOULD be simple, immutable DTOs.
    *   Properties SHOULD be `private final` and initialized via the constructor. Only getters should be exposed.
    *   They carry parameters for filtering, pagination, etc.
    *   Example:
        ```php
        // src/Context/Account/Application/Query/GetAccountDetails/GetAccountDetailsQuery.php
        declare(strict_types=1);

        namespace App\Context\Account\Application\Query\GetAccountDetails;

        final class GetAccountDetailsQuery
        {
            public function __construct(public readonly string $accountId) // Consider AccountId VO
            {
            }
        }
        ```
*   **Location**: `src/Context/{BoundedContext}/Application/Query/{UseCaseName}/`

### Query Handlers

Query Handlers are responsible for executing Queries and returning the requested data.

*   **Purpose**: To fetch data from persistence and prepare it for presentation.
*   **Naming**: `VerbNounQueryHandler.php`, corresponding to the Query (e.g., `GetAccountDetailsQueryHandler.php`).
*   **Structure**:
    *   MUST have a single public method to handle the query, typically `__invoke({QueryName} $query)`.
    *   Dependencies (Repositories for read operations, direct DB connections for optimized read models) ARE INJECTED via the constructor.
    *   Return Value: SHOULD return a simple data structure (DTO, array) or a scalar type. Avoid returning raw Domain Entities directly, especially if the read model differs significantly from the write model or to prevent unintended modifications. This promotes separation and allows read models to be optimized independently.
    *   Example:
        ```php
        // src/Context/Account/Application/Query/GetAccountDetails/GetAccountDetailsQueryHandler.php
        declare(strict_types=1);

        namespace App\Context\Account\Application\Query\GetAccountDetails;

        // Assuming a DTO for the response
        use App\Context\Account\Application\Query\GetAccountDetails\AccountDetailsDTO;

        final class GetAccountDetailsQueryHandler
        {
            // For simple queries, a repository might be fine.
            // For complex read models, this might inject a DBAL connection or a specialized read service.
            public function __construct(private readonly AccountDetailsFinderInterface $accountFinder)
            {
            }

            public function __invoke(GetAccountDetailsQuery $query): ?AccountDetailsDTO
            {
                return $this->accountFinder->findDetails($query->accountId);
            }
        }
        ```
*   **Optimization**: Query Handlers can directly query databases (e.g., using Doctrine DBAL for complex reads or read-optimized projections) to build view models if performance is critical and fetching full aggregates is too slow. However, start with repository methods and optimize only when a bottleneck is identified and measured.
*   **Location**: Same directory as the corresponding Query.

### Buses (Command Bus & Query Bus)

We use Symfony Messenger to act as our Command Bus and Query Bus. This provides decoupling between the sender of a request and its handler.

*   **Dispatching**: Commands and Queries MUST be dispatched via their respective buses.
    ```php
    // Example in a Symfony Controller or API Platform Data Provider/Persister
    use Symfony\Component\Messenger\MessageBusInterface;

    class SomeController // ...
    {
        public function __construct(
            private readonly MessageBusInterface $commandBus,
            private readonly MessageBusInterface $queryBus
        ) {}

        public function someAction(): Response
        {
            // ...
            $this->commandBus->dispatch(new CreateAccountCommand('uuid', 'test@example.com', 0));
            // ...
            $accountDetails = $this->queryBus->dispatch(new GetAccountDetailsQuery('uuid'));
            // ...
        }
    }
    ```
*   **Configuration**: Bus configuration (middleware, routing messages to handlers) is handled in `config/packages/messenger.yaml`.

### Benefits of CQRS

*   **Separation of Concerns**: Clear distinction between write and read logic.
*   **Scalability**: Read and write workloads can be optimized and scaled independently.
*   **Flexibility**: Allows for different data models for querying and updating.
*   **Maintainability**: Simpler models for specific tasks make code easier to understand and manage.
*   **Testability**: Handlers are easier to test in isolation.

## Domain-Driven Design (DDD) Principles

Domain-Driven Design (DDD) is an approach to software development that emphasizes a deep understanding of the business domain. It helps in creating software that accurately models the business and its processes. We apply several DDD tactical patterns in this project.

### Bounded Contexts

*   **Definition**: A Bounded Context defines a specific responsibility area within the application where a particular domain model is consistent and well-defined. It sets explicit boundaries for a model.
*   **Our Implementation**: Each subdirectory within `src/Context/` (e.g., `src/Context/Account`, `src/Context/User`) represents a Bounded Context. This helps to isolate models and prevent concepts from one context from leaking into another inappropriately.
*   **Integration**: When Bounded Contexts need to interact, this is typically done via Application Services, Domain Events, or well-defined APIs (e.g., using API Platform).

### Ubiquitous Language

*   **Definition**: A common, rigorous language shared by the development team and domain experts. This language should be used in all project communications, documentation, and code (class names, method names, variables).
*   **Practice**: Strive to identify and use terms that accurately reflect the business domain. If the business calls something a "Customer Ledger," we should use `CustomerLedger` in our code, not `ClientAccountSheet`.

### Entities

*   **Definition**: Objects that are not fundamentally defined by their attributes, but rather by a thread of continuity and identity. They have an ID and a lifecycle.
*   **Characteristics**:
    *   **Identity**: Each entity has a unique identifier (e.g., `AccountId`, `UserId`). This ID remains constant throughout the entity's lifecycle. Prefer using dedicated Value Objects for IDs (e.g., `AccountId extends Uuid`).
    *   **Mutability**: Entities are typically mutable, meaning their state can change over time. Changes should be driven by methods that represent domain operations.
    *   **Lifecycle**: Entities are created, loaded, modified, and eventually may be archived or deleted.
*   **Location**: Typically within the `Domain/Model/` directory of a Bounded Context (e.g., `src/Context/Account/Domain/Model/Account.php`).
*   **Example**:
    ```php
    // src/Context/Account/Domain/Model/Account.php
    declare(strict_types=1);

    namespace App\Context\Account\Domain\Model;

    class Account extends AggregateRoot // Assuming AggregateRoot handles domain events
    {
        private AccountId $id;
        private string $email;
        private Money $balance;
        private bool $isActive;

        // Private constructor to enforce creation via named constructors/factory methods
        private function __construct(AccountId $id, string $email, Money $balance)
        {
            $this->id = $id;
            $this->email = $email; // Email VO would be better
            $this->balance = $balance;
            $this->isActive = true;
            // Record event: AccountWasCreated
        }

        public static function create(AccountId $id, string $email, Money $initialBalance): self
        {
            $account = new self($id, $email, $initialBalance);
            $account->record(new AccountWasCreated($id, $email, $initialBalance->getAmount())); // Example event
            return $account;
        }

        public function deposit(Money $amount): void
        {
            if (!$this->isActive) {
                throw new AccountNotActiveException($this->id);
            }
            if ($amount->isNegativeOrZero()) {
                throw new InvalidDepositAmountException('Amount must be positive.');
            }
            $this->balance = $this->balance->add($amount);
            $this->record(new FundsDeposited($this->id, $amount));
        }

        public function withdraw(Money $amount): void
        {
            // ... similar logic, invariants, and event recording
        }

        public function deactivate(): void
        {
            $this->isActive = false;
            $this->record(new AccountDeactivated($this->id));
        }

        public function getId(): AccountId
        {
            return $this->id;
        }
        // Other getters...
    }
    ```

### Value Objects

*   **Definition**: Objects that describe a characteristic or attribute and are identified by their values, not by an ID. They have no conceptual identity.
*   **Characteristics**:
    *   **Immutability**: Value Objects SHOULD BE immutable. Once created, their state cannot be changed. Any operation that seems to modify a Value Object should return a new instance.
    *   **Equality**: Two Value Objects are equal if all their constituent values are equal. Implement an `equals()` method.
    *   **Validation**: Validation logic for the value(s) it holds should be part of the Value Object itself (e.g., an `Email` VO validates email format).
    *   **Self-Documenting**: Using `Email` instead of `string` for an email makes the code more expressive.
*   **Location**: Typically within `Domain/Model/` or `Domain/ValueObject/` in a Bounded Context, or `Shared/Domain/ValueObject/` for shared VOs.
*   **Examples**: `Money`, `EmailAddress`, `Address`, `AccountId` (if it's just a wrapper around a UUID/string with validation).
*   **Example (`Money` VO - simplified)**:
    ```php
    // src/Context/Account/Domain/Model/Money.php (or Shared/Domain/ValueObject/Money.php)
    declare(strict_types=1);

    namespace App\Context\Account\Domain\Model; // Or App\Shared\Domain\ValueObject;

    final class Money
    {
        private int $amount; // Store money in cents to avoid floating point issues
        private Currency $currency; // Another VO

        public function __construct(int $amount, Currency $currency)
        {
            if ($amount < 0 && !$this->isAllowedNegative()) { // Example validation
                throw new \InvalidArgumentException('Amount cannot be negative for this context.');
            }
            $this->amount = $amount;
            $this->currency = $currency;
        }

        public function add(Money $other): self
        {
            if (!$this->currency->equals($other->currency)) {
                throw new \InvalidArgumentException('Cannot add money of different currencies.');
            }
            return new self($this->amount + $other->amount, $this->currency);
        }

        public function equals(Money $other): bool
        {
            return $this->amount === $other->amount && $this->currency->equals($other->currency);
        }

        public function getAmount(): int { return $this->amount; }
        public function getCurrency(): Currency { return $this->currency; }

        // Helper for specific contexts if needed
        private function isAllowedNegative(): bool { return false; }
    }
    ```

### Aggregates and Aggregate Roots

*   **Definition**: An Aggregate is a cluster of domain objects (Entities and Value Objects) that can be treated as a single unit. The Aggregate Root is a specific Entity within the Aggregate that serves as the entry point for all operations on that Aggregate.
*   **Purpose**:
    *   To maintain consistency of business rules within the Aggregate.
    *   To define clear ownership and boundaries.
*   **Rules**:
    *   The Aggregate Root is the only member of the Aggregate that external objects are allowed to hold references to. Other objects within the Aggregate can be reached via traversal from the Root.
    *   Operations on the Aggregate MUST go through the Aggregate Root.
    *   If a change spans multiple Aggregates, it's managed through eventual consistency, often using Domain Events, or via Application Services coordinating multiple Aggregate operations within a single transaction.
    *   Aggregates are transactional consistency boundaries. A single transaction should only modify a single Aggregate instance.
*   **Example**: An `Order` might be an Aggregate Root, and `OrderItem` objects would be part of the `Order` Aggregate. You wouldn't load or save an `OrderItem` directly; you'd load the `Order` and access its items through it.
*   **Location**: Aggregate Roots are Entities, typically in `Domain/Model/`.

### Domain Events

*   **Definition**: A Domain Event is an object that represents something significant that has happened in the domain.
*   **Purpose**:
    *   To capture important business occurrences.
    *   To enable side effects to be handled explicitly and decoupled from the initial operation (e.g., sending an email after an order is placed).
    *   To facilitate communication between Aggregates or Bounded Contexts (often leading to eventual consistency).
*   **Characteristics**:
    *   **Immutable**: Once an event occurs, it's a fact and cannot be changed.
    *   **Naming**: Past tense (e.g., `OrderPlaced`, `UserRegistered`, `AccountDeactivated`).
    *   **Content**: Should contain all relevant data about what happened (e.g., `OrderPlaced` event would include `orderId`, `customerId`, `totalAmount`). Include primitive types or Value Objects, not full Entities.
*   **Dispatching**:
    1.  Aggregates record events when their state changes (e.g., `$this->record(new AccountWasCreated(...));`).
    2.  An Application Service (e.g., Command Handler) or Infrastructure component (e.g., Doctrine event listener) retrieves these events from the Aggregate *after* the main operation is successfully persisted.
    3.  Events are then dispatched via an Event Bus (e.g., Symfony Messenger).
*   **Handlers/Listeners**: Other parts of the system (in the same or different Bounded Contexts) can subscribe to these events to perform actions.
*   **Location**: `Domain/Event/` within the Bounded Context where the event originates.

### Domain Services

*   **Definition**: When some domain logic doesn't naturally belong to an Entity or Value Object, it can be encapsulated in a Domain Service.
*   **Characteristics**:
    *   Stateless (or state related to the operation, not held between calls).
    *   Interfaces defined in the Domain layer, implementations might also be in Domain if purely domain logic, or Infrastructure if they coordinate with external systems (though this blurs lines).
    *   Often operate on multiple Aggregates or coordinate complex calculations.
*   **Example**: A `FundTransferService` that orchestrates withdrawing from one `Account` and depositing into another, ensuring domain rules for transfers are met. This service would use `AccountRepository` to load accounts and then call methods on the `Account` entities.
*   **Location**: Interfaces in `Domain/Service/`, implementations can be there or in `Infrastructure/Service/` if they have infra concerns.

### Repositories

*   **Definition**: Mediate between the domain and data mapping layers using a collection-like interface for accessing domain objects (Aggregates).
*   **Purpose**: To encapsulate the logic for retrieving and storing Aggregate Roots, abstracting away the persistence mechanism (e.g., database).
*   **Characteristics**:
    *   **Interfaces in Domain**: Repository interfaces are defined in the Domain layer, alongside the Aggregates they manage (e.g., `AccountRepositoryInterface`). This keeps the Domain layer persistence-agnostic.
    *   **Implementations in Infrastructure**: Concrete implementations (e.g., `DoctrineAccountRepository`) reside in the Infrastructure layer.
    *   **Operate on Aggregates**: Methods should be named to reflect operations on Aggregates (e.g., `findById(AccountId $id): ?Account`, `save(Account $account): void`, `findActiveAccounts(): AccountCollection`).
    *   **Collection-like Behavior**: They provide an illusion of an in-memory collection of Aggregates.
    *   **Transaction Management**: Repositories typically don't manage transactions themselves. Transactions are usually handled by Application Services (Command Handlers) or framework mechanisms.
*   **Location**: Interfaces in `Domain/Repository/`, implementations in `Infrastructure/Persistence/`.

By applying these DDD patterns, we aim to create a model that is closely aligned with the business domain, leading to a more robust, understandable, and maintainable application.

## Testing Strategy and Standards

Automated testing is a cornerstone of our development process, ensuring code quality, preventing regressions, and providing confidence when refactoring or adding new features. We primarily use PHPUnit with Mockery for mocking and FakerPHP for test data generation.

### General Principles

*   **Arrange-Act-Assert (AAA)**: All tests SHOULD follow the AAA pattern for clarity:
    *   **Arrange**: Set up the necessary preconditions and inputs. This includes instantiating objects, preparing mocks, and setting up test data.
    *   **Act**: Execute the method or code being tested.
    *   **Assert**: Verify that the outcome (return value, state change, exception thrown, event dispatched) is as expected.
*   **Test Naming**:
    *   **Test Classes**: Suffix the class name with `Test` (e.g., `UserServiceTest.php`, `AccountTest.php`).
    *   **Test Methods**: Use `camelCase` prefixed with `test` (e.g., `testCreateUserSuccessfully()`, `testThrowExceptionIfPasswordIsTooShort()`). The method name should clearly describe the scenario being tested.
*   **Test Data**:
    *   **Object Mothers**: Use Object Mothers (e.g., `UserMother.php`, `OrderMother.php` located in `tests/Context/{BoundedContext}/Domain/Mother/`) to create complex domain objects for tests. They help centralize test data creation and make tests more readable.
    *   **FakerPHP**: Utilize FakerPHP (available in the project) for generating realistic and varied fake data (names, addresses, text, etc.) where specific values aren't crucial to the test logic.
    *   **Specificity**: Use only the data required for the test. Avoid overly complex fixtures.
*   **Assertions**:
    *   Use the most specific PHPUnit assertion methods possible (e.g., `assertSame()` instead of `assertTrue($a == $b)`, `assertCount()`, `assertInstanceOf()`, `assertStringContainsString()`).
    *   Assert only what is relevant to the test.
*   **Test Independence**: Each test MUST be able to run independently and in any order. Tests should not rely on the state left by previous tests. Clean up global state if necessary (e.g., `Mockery::close()` in `tearDown()`).
*   **Readability**: Tests serve as living documentation. Write them to be clear, concise, and easy to understand.
*   **`setUp()` and `tearDown()`**:
    *   Use the `setUp()` method for common initialization logic needed by multiple tests in a class (e.g., instantiating the class under test, setting up common mocks).
    *   Use `tearDown()` for cleanup, especially `Mockery::close()` to verify mock expectations and clean up Mockery's global state.
        ```php
        protected function tearDown(): void
        {
            parent::tearDown(); // If extending a base test class that has a tearDown
            \Mockery::close();
        }
        ```

### Types of Tests

1.  **Unit Tests**
    *   **Purpose**: To verify the smallest pieces of code (individual classes or methods) in isolation from their dependencies.
    *   **Focus**: Test business logic within Entities, Value Objects, Domain Services, Application Services (Command/Query Handlers), etc.
    *   **Mocking**:
        *   Heavily rely on mocking (using Mockery) to isolate the unit under test.
        *   Mock collaborators (dependencies passed to the class constructor or methods) that are not the focus of the current test.
        *   Do NOT mock Value Objects or simple data structures. Test with real instances.
        *   Do NOT typically mock the class being tested itself (unless for partial mocking in specific, rare scenarios).
    *   **Speed**: Must be very fast. A slow unit test suite hinders development flow.
    *   **Location**: Mirror the `src/` structure, e.g., a test for `src/Context/Account/Application/Command/CreateAccountCommandHandler.php` would be `tests/Context/Account/Application/Command/CreateAccountCommandHandlerTest.php`.

2.  **Integration Tests**
    *   **Purpose**: To verify the interaction between several components or layers of the application.
    *   **Focus**:
        *   Test repository implementations against a real (test) database.
        *   Test Command/Query handlers with their real repository dependencies.
        *   Test interaction with external services (though these are often still mocked at the boundary for stability and speed, unless the external service offers a sandbox).
    *   **Database**:
        *   Use a dedicated test database, configured in `.env.test`.
        *   Symfony's `KernelTestCase` can be used to access services, including Doctrine.
        *   Manage database state carefully:
            *   Use transactions and roll them back after each test (e.g., using Doctrine's `beginTransaction()` and `rollBack()` in `setUp`/`tearDown`).
            *   Alternatively, use database seeding strategies (e.g., DoctrineFixturesBundle, but be mindful of speed) or truncate tables.
    *   **Speed**: Slower than unit tests. Write them for critical interaction points, not for every possible scenario.
    *   **Location**: Often in the `Infrastructure` test directories (e.g., `tests/Context/Account/Infrastructure/Persistence/DoctrineAccountRepositoryTest.php`) or for testing application service wiring.

3.  **API / Functional Tests (End-to-End)**
    *   **Purpose**: To test the application from an external perspective, by making HTTP requests to API endpoints and verifying responses.
    *   **Focus**: Verify that API endpoints behave as expected, including request handling, authentication/authorization, response codes, headers, and payloads.
    *   **Tools**:
        *   Extend `ApiPlatform\Symfony\Bundle\Test\ApiTestCase` for API Platform endpoints. This provides convenient methods for making requests and assertions.
        *   For non-API Platform web pages or more custom functional tests, `Symfony\Bundle\FrameworkBundle\Test\WebTestCase` can be used.
    *   **Assertions**: Check HTTP status codes, response headers (e.g., `Content-Type`), and response body content (JSON structure and values).
    *   **Database**: Similar to integration tests, requires managing database state. `ApiTestCase` often facilitates this.
    *   **Authentication**: `ApiTestCase` provides helpers for authenticating as a user.
    *   **Speed**: Typically the slowest type of test. Focus on testing key user flows and critical API endpoints.
    *   **Location**: Can be in a dedicated `tests/Api/` or `tests/Functional/` directory, or per-context, e.g., `tests/Context/Account/UI/Http/Controller/AccountControllerTest.php` or `tests/Context/Account/UI/Http/Resource/AccountResourceTest.php`.

### Test Coverage

*   **Goal**: Aim for good, meaningful test coverage of business logic, critical paths, and edge cases.
*   **Quality over Quantity**: A high percentage of coverage with trivial tests is less valuable than slightly lower coverage with robust tests for complex logic.
*   **Reporting**: You can generate a coverage report using PHPUnit:
    ```bash
    XDEBUG_MODE=coverage bin/phpunit --coverage-html var/coverage
    ```
    Review these reports to identify untested areas of critical code.
*   **Continuous Integration (CI)**: Coverage checks can be integrated into the CI pipeline.

By adhering to these standards and employing a mix of test types, we can build a more robust and maintainable application.

## Version Control (Git) Best Practices

A disciplined approach to version control using Git is crucial for team collaboration, maintaining a clean project history, and enabling effective code management.

### Branching Strategy

We follow a simplified Gitflow-like branching model:

*   **`main`**:
    *   This branch represents the latest production-ready code.
    *   Direct commits to `main` are strictly forbidden.
    *   Code is merged into `main` from `develop` during a release process, or from `hotfix/*` branches.
    *   Tagged with version numbers (e.g., `v1.0.0`, `v1.0.1`).
*   **`develop`**:
    *   This is the primary development branch where all completed features and fixes are integrated.
    *   All feature branches are created from `develop` and merged back into `develop`.
    *   Should always be stable enough for internal releases or QA.
*   **`feature/{issue-id}-{short-description}`**:
    *   For developing new features or improvements (e.g., `feature/PROJ-123-user-registration`, `feature/TASK-45-improve-caching`).
    *   Branched from `develop`.
    *   Once complete and reviewed, merged back into `develop` (typically via a Pull Request).
    *   Should be short-lived.
*   **`fix/{issue-id}-{short-description}`**:
    *   For non-critical bug fixes that are part of the ongoing development cycle (e.g., `fix/PROJ-234-incorrect-calculation`).
    *   Branched from `develop`.
    *   Merged back into `develop` (typically via a Pull Request).
*   **`hotfix/{issue-id}-{short-description}`**:
    *   For critical bugs found in production that need immediate fixing (e.g., `hotfix/PROJ-301-critical-login-vulnerability`).
    *   Branched from `main`.
    *   Once complete, merged directly into `main` AND then immediately into `develop` to ensure the fix is incorporated into ongoing development.
    *   Tagged appropriately after merging to `main`.

**General Branching Guidelines**:
*   Keep branches focused and short-lived.
*   Delete branches after they are merged.
*   Regularly update your feature/fix branches with the latest changes from `develop` using `git pull origin develop --rebase` to maintain a cleaner history and resolve conflicts sooner.

### Commit Messages

We adhere to the **Conventional Commits** specification (see [www.conventionalcommits.org](https://www.conventionalcommits.org)) for all commit messages. This provides a structured history, making it easier to understand changes and automate changelog generation.

**Format**:
```
type(scope): subject

body (optional)

footer (optional)
```

*   **`type`**: Must be one of the following:
    *   `feat`: A new feature.
    *   `fix`: A bug fix.
    *   `docs`: Documentation only changes.
    *   `style`: Changes that do not affect the meaning of the code (white-space, formatting, missing semi-colons, etc).
    *   `refactor`: A code change that neither fixes a bug nor adds a feature.
    *   `perf`: A code change that improves performance.
    *   `test`: Adding missing tests or correcting existing tests.
    *   `build`: Changes that affect the build system or external dependencies (e.g., composer, npm).
    *   `ci`: Changes to our CI configuration files and scripts.
    *   `chore`: Other changes that don't modify `src` or `tests` files (e.g., updating .gitignore).
*   **`scope` (optional)**: A noun describing the section of the codebase affected (e.g., `Account`, `Order`, `APIPlatform`).
    *   Example: `feat(Account): add user profile endpoint`
*   **`subject`**:
    *   A concise description of the change (max 50-72 characters).
    *   Use the imperative mood, present tense (e.g., "Add feature" not "Added feature" or "Adds feature").
    *   No capitalization at the beginning, no period at the end.
*   **`body` (optional)**:
    *   A more detailed explanation of the changes. Use it to explain "what" and "why" vs. "how".
    *   Separate from the subject with a blank line.
*   **`footer` (optional)**:
    *   For referencing issue tracker IDs (e.g., `Closes #123`, `Fixes PROJ-456`).
    *   For breaking changes, start with `BREAKING CHANGE:` followed by a description of the change.

**Examples**:
```
feat: allow users to change their email address

Users can now update their email address through the profile settings page.
This required adding a new application service and updating the User entity.

Closes #78
```
```
fix(Auth): prevent login with expired password

BREAKING CHANGE: Users with expired passwords will now be redirected to
a password reset page instead of seeing an error message.
```

### Pull Requests (PRs)

All changes to `develop` and `main` (via hotfixes) MUST be made through Pull Requests (PRs). Direct pushes to these branches are disabled.

*   **Creation**: Create PRs when your feature/fix is complete and ready for review. Push your branch to the remote repository and create the PR via the Git hosting platform (e.g., GitHub, GitLab).
*   **Title and Description**:
    *   PR titles should be clear and follow a similar style to commit subjects (e.g., `feat(Order): Implement order cancellation feature`).
    *   The PR description should clearly explain the purpose of the changes, what was done, and how it can be tested. Reference any relevant issue numbers.
*   **PR Template**: We SHOULD use a PR template (e.g., `.github/pull_request_template.md`). This template should prompt for:
    *   Link to the related issue(s).
    *   Summary of changes.
    *   How to test the changes.
    *   Screenshots/GIFs for UI changes.
    *   Notes for reviewers.
*   **Review Process**:
    *   At least one other developer MUST review and approve the PR before it can be merged. (Detailed in "Code Review Process").
    *   Address all review comments and push updates to the same branch.
*   **CI Checks**: All automated checks (tests, linting, static analysis defined in `composer.json` scripts) MUST pass. PRs with failing checks cannot be merged.
*   **Merging**:
    *   **Into `develop`**: Prefer **"Squash and Merge"** for `feature/*` and `fix/*` branches into `develop`. This keeps the `develop` history clean with one commit per feature/fix. The squashed commit message should follow Conventional Commits format.
    *   **Into `main`**: `hotfix/*` branches are merged into `main` with a standard merge commit. `develop` is merged into `main` during a release process, also with a standard merge commit (often tagged).

### General Git Hygiene

*   **Commit Small and Often**: Make small, logical commits that represent a single unit of work. This makes reviews easier and helps pinpoint issues.
*   **No Commented-Out Code**: Do not commit commented-out code. If it's not needed, remove it. Version control is there if you need to look back.
*   **Update `.gitignore`**: Ensure temporary files, IDE settings, local environment files (`.env.local`), and vendor directories (`vendor/`, `tools/php-cs-fixer/vendor/`) are in `.gitignore`. (The project already has a `.gitignore`, ensure it's maintained).
*   **Stay Updated**: Regularly pull the latest changes from the remote `develop` branch into your local `develop` and rebase your active feature/fix branches on top of it:
    ```bash
    git checkout develop
    git pull origin develop
    git checkout feature/my-feature-branch
    git rebase develop
    ```
    This helps to avoid large merge conflicts later. Resolve any rebase conflicts locally before pushing.
*   **Force Pushing**: Avoid force pushing (`git push -f`) to shared branches (`develop`, `main`). For your own feature branches that haven't been PR'd yet or that you're the sole contributor to, force pushing after a rebase is acceptable, but communicate if others might have pulled that branch.

## Error Handling and Exceptions

A robust error handling strategy is essential for building reliable applications. Our approach emphasizes clear, context-rich exceptions and appropriate logging.

### Types of Exceptions

We categorize exceptions based on the layer where they originate or are most relevant:

1.  **Domain Exceptions**:
    *   **Purpose**: Represent errors related to business rule violations or invalid domain states. They are part of the Ubiquitous Language.
    *   **Characteristics**: Should be specific and meaningful to the domain (e.g., `InsufficientFundsException`, `OrderCannotBeCancelledException`, `InvalidEmailFormatException`).
    *   **Location**: `src/Context/{BoundedContext}/Domain/Exception/`.
    *   **Base Class**: Consider creating a base `DomainException` marker interface or class within each Bounded Context or in `Shared/Domain` if common characteristics exist.
        ```php
        // src/Context/Order/Domain/Exception/OrderException.php
        namespace App\Context\Order\Domain\Exception;

        class OrderException extends \DomainException // Or extends \RuntimeException for marker
        {
        }

        // src/Context/Order/Domain/Exception/OrderCannotBeCancelledException.php
        namespace App\Context\Order\Domain\Exception;

        class OrderCannotBeCancelledException extends OrderException
        {
            public function __construct(string $orderId, string $reason)
            {
                parent::__construct(sprintf('Order "%s" cannot be cancelled: %s', $orderId, $reason));
            }
        }
        ```

2.  **Application Exceptions**:
    *   **Purpose**: Represent errors in the application logic, such as invalid input that passed initial framework validation but is unsuitable for a use case, or issues orchestrating domain actions.
    *   **Characteristics**: Bridge between framework/infrastructure errors and domain understanding (e.g., `UserNotAuthenticatedAppException`, `ExternalServiceIntegrationAppException`).
    *   **Location**: `src/Context/{BoundedContext}/Application/Exception/`.

3.  **Infrastructure Exceptions**:
    *   **Purpose**: Represent failures in infrastructure components (e.g., database connection error, network issue when calling an external API, file system error).
    *   **Characteristics**: Often technical in nature. These might be caught and re-thrown as more specific Application or Domain exceptions if they have a clear business meaning.
    *   **Location**: `src/Context/{BoundedContext}/Infrastructure/Exception/` (or a general one if not context-specific, e.g., `Shared/Infrastructure/Exception`).

### Exception Handling Strategy

*   **Specificity**: Throw the most specific exception possible.
*   **Catching and Re-throwing (Wrapping)**:
    *   Catch generic exceptions from external libraries or lower layers (e.g., `PDOException` from Doctrine, Guzzle's `ConnectException`) within your Infrastructure or Application services.
    *   Wrap them in more meaningful Domain or Application exceptions if the error has a specific implication for the current operation. This decouples your core logic from underlying implementation details.
        ```php
        // Example in a Repository implementation
        try {
            // ... Doctrine call ...
        } catch (\Doctrine\ORM\NoResultException $e) {
            throw new ProductNotFoundException($productId, $e);
        } catch (\Doctrine\DBAL\Exception\ConnectionException $e) {
            throw new StorageUnavailableInfrastructureException('Database connection failed', 0, $e);
        }
        ```
*   **Avoid Catching Generic Exceptions**: Do not catch `\Exception` or `\Throwable` unless at the very top of the execution stack (e.g., a global error handler in `public/index.php` or a base console command). Let specific exceptions propagate to be handled by the framework or higher-level services.
*   **Framework Handling**: Symfony and API Platform provide robust mechanisms to convert exceptions into appropriate HTTP responses. Leverage this.

### Exception Messages and Context

*   **Clarity**: Messages should be clear, concise, and provide enough information to understand the error.
*   **Contextual Data**: Include relevant identifiers or parameters (e.g., "Order with ID 'xyz-123' not found.").
*   **Security**: **Do NOT include sensitive data** (passwords, PII beyond necessary identifiers) in exception messages that might be logged or exposed to users.
*   **User-Facing Messages**: For user-facing errors, messages should be user-friendly. The exception message itself might be technical; the transformation to a user-friendly message can happen in the presentation layer or via API Platform's error normalization.

### HTTP Status Codes (API Context)

Exceptions are typically mapped to HTTP status codes in an API context. API Platform handles many of these automatically for recognized exception types. Common mappings include:
*   `400 Bad Request`: For client-side errors like invalid input format or data that fails validation (e.g., `Symfony\Component\Validator\Exception\ValidationFailedException`, custom application validation exceptions).
*   `401 Unauthorized`: For authentication failures (e.g., `Symfony\Component\Security\Core\Exception\AuthenticationException`).
*   `403 Forbidden`: When authentication succeeded but the user lacks permission for the action (e.g., `Symfony\Component\Security\Core\Exception\AccessDeniedException`).
*   `404 Not Found`: When a requested resource does not exist (e.g., `App\Context\SomeResource\Domain\Exception\SomeResourceNotFoundException`).
*   `409 Conflict`: When an action cannot be completed because of a conflict with the current state of the resource (e.g., trying to create a resource that already exists with a unique constraint).
*   `422 Unprocessable Entity`: Often used if the syntax of the request is correct, but semantic errors prevent processing (alternative to 400 for some validation scenarios).
*   `500 Internal Server Error`: For unexpected server-side errors (most unhandled exceptions, infrastructure failures).
*   `503 Service Unavailable`: For temporary operational issues (e.g., database down, external service overloaded).

API Platform allows customization of this mapping. See `config/packages/api_platform.yaml` under `exception_to_status`.

### Logging Strategy

Effective logging is crucial for debugging and monitoring. We use Symfony's Monolog integration.

*   **What to Log**:
    *   **Errors/Critical**: All unhandled exceptions, significant infrastructure failures, security-related incidents. These should always be logged with as much detail as possible (including stack traces).
    *   **Warnings**: Recoverable errors, deprecated API usage, unusual but not critical situations.
    *   **Info**: Significant application lifecycle events (e.g., service startup/shutdown, important business transactions completed). Use sparingly to avoid excessive log volume.
    *   **Debug**: Detailed information for development/debugging. Should generally be disabled in production.
*   **Log Levels (PSR-3)**: Adhere to standard log levels: `EMERGENCY`, `ALERT`, `CRITICAL`, `ERROR`, `WARNING`, `NOTICE`, `INFO`, `DEBUG`.
*   **Contextual Information**: Include relevant data in log messages (e.g., user ID, request ID, relevant parameters). Symfony's Monolog processors can help add this automatically.
*   **Configuration**: Logging behavior is configured in `config/packages/monolog.yaml` (and environment-specific files like `monolog.prod.yaml`).
*   **Sensitive Data**: Be extremely careful not to log sensitive information (passwords, API keys, full PII) unless absolutely necessary and properly secured. Use sanitization if needed.

### Testing Exceptions

*   Reiterate that application code MUST test that specific exceptions are thrown under expected error conditions.
*   PHPUnit provides `expectException()`, `expectExceptionMessage()`, `expectExceptionCode()`, etc., for this purpose.
    ```php
    public function testCreateOrderWithInvalidItemThrowsException(): void
    {
        $this->expectException(InvalidOrderItemException::class);
        $this->expectExceptionMessage('Item "NON_EXISTENT_ITEM" is not valid.');

        $this->orderService->createOrder(VALID_CUSTOMER_ID, [INVALID_ITEM_DATA]);
    }
    ```