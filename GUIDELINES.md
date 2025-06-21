# PHP Project Guidelines

Welcome to the PHP Project Guidelines. This document outlines the architectural principles, naming conventions, code structure, testing standards, and other best practices to be followed in this project. Adhering to these guidelines is crucial for building a maintainable, scalable, and robust application.

## Table of Contents

-   [Naming Conventions and Code Structure](#naming-conventions-and-code-structure)
  -   [General Naming Conventions](#general-naming-conventions)
  -   [Code Style](#code-style)
  -   [Root Directory Structure](#root-directory-structure)
  -   [`src/` Directory Deep Dive](#src-directory-deep-dive)
  -   [Specific Type Naming Conventions (Summary and Location)](#specific-type-naming-conventions-summary-and-location)
-   [Architectural Principles](#architectural-principles)
  -   [Vertical Slice Architecture](#vertical-slice-architecture)
  -   [Clean Architecture Layers](#clean-architecture-layers)
  -   [Domain-Driven Design (DDD) Principles](#domain-driven-design-ddd-principles)
  -   [CQRS (Command Query Responsibility Segregation) Guidelines](#cqrs-command-query-responsibility-segregation-guidelines)
  -   [Event Sourcing (ES) Concepts](#event-sourcing-es-concepts)
-   [Exhaustive Testing Standards](#exhaustive-testing-standards)
  -   [General Testing Philosophy](#general-testing-philosophy)
  -   [Test Organization](#test-organization)
  -   [Unit Testing Standards](#unit-testing-standards)
  -   [Object Mother Pattern](#object-mother-pattern)
  -   [Naming Conventions for Tests](#naming-conventions-for-tests)
  -   ["Exhaustive" Testing - What to Cover](#exhaustive-testing---what-to-cover)
  -   [Integration Testing Guidelines](#integration-testing-guidelines)
  -   [Code Coverage](#code-coverage)
-   [Cross-Cutting Concerns](#cross-cutting-concerns)
  -   [Error Handling and Exception Strategy](#error-handling-and-exception-strategy)
  -   [Logging](#logging)
  -   [Configuration Management](#configuration-management)
  -   [Security (General Guidelines)](#security-general-guidelines)
-   [Final Recommendations](#final-recommendations)

## Naming Conventions and Code Structure

This section details the naming conventions and the overall code structure of the project, aligning with Vertical Slice Architecture, Clean Architecture, and Domain-Driven Design (DDD) principles. Adhering to these conventions is crucial for maintaining consistency and readability across the codebase.

### General Naming Conventions

-   **Classes & Interfaces:** `PascalCase` (e.g., `CreateAccountCommandHandler`, `AccountRepository`, `Account`).
-   **Methods:** `camelCase` (e.g., `handleMessage`, `findOneByIdOrFail`, `enableAccount`).
-   **Variables & Properties:** `camelCase` (e.g., `$accountId`, `private string $currentBalance;`).
-   **Constants:** `SCREAMING_SNAKE_CASE` (e.g., `const MAX_LOGIN_ATTEMPTS = 5;`).
-   **PHP Files:** `PascalCase.php`, matching the primary class, interface, or trait they contain (e.g., `Account.php`, `AccountRepository.php`).
-   **Namespaces:** Follow the directory structure. The base namespace is `App\`. For example, `App\Context\Account\Domain\Account` for the Account aggregate.

### Code Style

-   Adherence to **PSR-12** (Extended Coding Style Guide) is mandatory.
-   The project uses **PHP CS Fixer** with the configuration defined in `.php-cs-fixer.dist.php` to automate style fixing. Ensure code is formatted using the fixer before committing.
-   **Type Declarations:** Add type declarations for all properties, method parameters, and return types where possible.
-   **PHPDoc Blocks:** Consider adding PHPDoc blocks for classes, methods, and properties where their purpose or usage isn't immediately obvious from the name and type hints, or for providing more context (e.g., `@throws` annotations).

### Root Directory Structure

-   `src/`: Contains all source code for the application.
-   `tests/`: Contains all tests, mirroring the `src/` structure.

### `src/` Directory Deep Dive

#### `src/Context/{BoundedContextName}/`

Each Bounded Context (e.g., `Account`, `User`, `Order`) resides in its own directory within `src/Context/`. This isolates domain models and business logic specific to that part of the business.

-   **Context Configuration:**
  -   `src/Context/{BoundedContextName}/{contextname}.yaml` (e.g., `src/Context/Account/account.yaml`): This file is used for context-specific Dependency Injection services and parameters.

-   **Layers within a Bounded Context:**

  -   **`Application/`**: Contains the application logic that orchestrates domain objects to perform use cases.
    -   `UseCase/{ActionName}/`: Organizes all files related to a specific use case (Vertical Slice).
      -   `{ActionName}Command.php`: The command object for the use case.
      -   `{ActionName}Query.php`: The query object (if the use case is a query).
      -   `{ActionName}CommandHandler.php`: The handler for the command.
      -   `{ActionName}QueryHandler.php`: The handler for the query.
      -   Request/Response DTOs: If a use case requires specific input/output structures not covered by Command/Query objects, they can be placed here (e.g., `{ActionName}Response.php`).

  -   **`Domain/`**: The heart of the Bounded Context, containing all domain logic and objects, independent of application or infrastructure concerns.
    -   `Bus/`: Contains Domain Event classes specific to this context (e.g., `AccountWasCreated.php`, `AccountWasDisabled.php`).
      -   *Convention:* `{AggregateName}Was{PastTenseAction}.php` or `{AggregateName}{PastTenseVerb}.php`.
    -   `Exception/`: Custom domain exceptions specific to this Bounded Context (e.g., `AccountNotFoundException.php`, `InsufficientBalanceException.php`).
      -   *Convention:* `{ErrorDescription}Exception.php`. Exceptions should have meaningful messages providing context.
    -   Aggregate Roots (e.g., `Account.php`).
      -   *Convention:* Noun representing the concept.
    -   Entities (if not ARs).
    -   Value Objects (e.g., `AccountId.php`, `AccountCode.php`, `EmailAddress.php`).
      -   *Convention:* Descriptive name, often ending with `Id` for identifiers.
    -   Repository Interfaces (e.g., `AccountRepository.php`).
      -   *Convention:* `{AggregateName}Repository.php`.

  -   **`Infrastructure/`**: Contains implementations of external concerns (database, APIs, framework bindings).
    -   `Persistence/{Technology}/` (e.g., `Persistence/Doctrine/`): Houses repository implementations.
      -   `{Technology}{AggregateName}Repository.php` (e.g., `DoctrineAccountRepository.php`).
    -   `Http/`: For components related to the HTTP interface.
      -   `Controller/`: API controllers specific to this Bounded Context's use cases (e.g., `CreateAccountController.php`).
      -   `Dto/`: Request/Response DTOs used by controllers if they differ from application layer DTOs or Commands/Queries (e.g., `CreateAccountApiRequest.php`).
    -   Other subdirectories as needed for different infrastructure concerns (e.g., `Messaging/`, `ExternalServices/`).

#### `src/Shared/`

Contains code that is genuinely shared across multiple Bounded Contexts. This code must still adhere to Clean Architecture principles (dependencies point inwards).

-   **`Application/`**:
  -   Base interfaces or classes for Commands, Queries, Handlers (e.g., `Command.php`, `Query.php`, `CommandHandler.php`, `QueryHandler.php`).
  -   Shared application services if any (use with caution to avoid coupling contexts).
-   **`Domain/`**:
  -   Generic base classes for domain objects (e.g., `AggregateRoot.php`, `DomainEvent.php`).
  -   Common Value Objects used across contexts (e.g., `SimpleUuid.php`, `StringValueObject.php`, `Money.php`).
-   **`Infrastructure/`**:
  -   Shared infrastructure components like common message bus implementations, base API controller logic, or shared authentication/authorization utilities.

### Specific Type Naming Conventions (Summary and Location)

-   **Commands:** `{ActionName}Command.php` -> `src/Context/{BC}/Application/UseCase/{ActionName}/`
-   **Command Handlers:** `{ActionName}CommandHandler.php` -> `src/Context/{BC}/Application/UseCase/{ActionName}/`
-   **Queries:** `{DataToRetrieve}Query.php` or `{ActionName}Query.php` -> `src/Context/{BC}/Application/UseCase/{ActionName}/`
-   **Query Handlers:** `{DataToRetrieve}QueryHandler.php` or `{ActionName}QueryHandler.php` -> `src/Context/{BC}/Application/UseCase/{ActionName}/`
-   **Domain Events:** `{AggregateName}Was{PastTenseAction}.php` -> `src/Context/{BC}/Domain/Bus/`
-   **Aggregates/Entities:** `{EntityName}.php` -> `src/Context/{BC}/Domain/`
-   **Value Objects:** `{VOName}.php` -> `src/Context/{BC}/Domain/` (or `src/Shared/Domain/`)
-   **Repository Interfaces:** `{AggregateName}Repository.php` -> `src/Context/{BC}/Domain/`
-   **Repository Implementations:** `{Technology}{AggregateName}Repository.php` -> `src/Context/{BC}/Infrastructure/Persistence/{Technology}/`
-   **Domain Exceptions:** `{ExceptionName}Exception.php` -> `src/Context/{BC}/Domain/Exception/`
-   **Application/Infrastructure DTOs:** `{Purpose}Dto.php` or `{Purpose}Request.php`/`{Purpose}Response.php` -> Respective `Application/UseCase/{ActionName}/` or `Infrastructure/Http/Dto/`
-   **Test Classes:** `{ClassNameUnderTest}Test.php` -> `tests/Context/{BC}/...` (mirroring src)
-   **Object Mothers:** `{ClassName}Mother.php` -> `tests/Context/{BC}/Domain/` (primarily for domain objects)

This structure and naming convention aims to provide a clear, scalable, and maintainable codebase that reflects the architectural principles outlined in these guidelines.

## Architectural Principles

This project adheres to a combination of widely recognized architectural principles to ensure a robust, scalable, and maintainable system. The following sections detail these core principles.

### Vertical Slice Architecture

A **Vertical Slice** represents a single, cohesive feature or use case within the application. Each slice should be self-contained, encompassing all the necessary components to deliver that specific functionality. This approach promotes high cohesion within a slice and low coupling between slices.

The project structure under `src/Context/{BoundedContext}/Application/UseCase/{Action}` is a good example of how a vertical slice is organized.

#### Components of a Vertical Slice

A typical vertical slice in this project will include the following components:

-   **Command/Query Objects**: These are simple data transfer objects (DTOs) that carry the input data for the use case (e.g., `CreateAccountCommand` or `GetAccountByIdQuery`). They reside within the `{Action}` directory.
-   **Command/Query Handlers**: These are the core of the slice, containing the logic to process the command or query. They orchestrate interactions with domain objects and repositories (e.g., `CreateAccountCommandHandler`). They also reside within the `{Action}` directory.
-   **Domain Objects**: These are the Aggregates, Entities, and Value Objects relevant to the specific feature. For instance, a `CreateAccount` slice would involve the `Account` aggregate and its associated value objects. These are typically located in the `src/Context/{BoundedContext}/Domain` directory but are directly utilized and often defined by the needs of the slice.
-   **Repository Interfaces**: If the slice requires data persistence or retrieval, it will define and use repository interfaces (e.g., `AccountRepository`). These interfaces are part of the Domain layer, typically within `src/Context/{BoundedContext}/Domain`.
-   **Domain Events**: If the execution of the slice results in significant state changes that other parts of the system might be interested in, it may raise Domain Events. These are also part of the Domain layer.
-   **Infrastructure Implementations**: Concrete implementations of repository interfaces or other external services (like email services, payment gateways) are placed in the `src/Context/{BoundedContext}/Infrastructure` directory. While some infrastructure can be shared, a slice might necessitate specific implementations if its requirements are unique.
-   **Tests**: Comprehensive tests for all components of the slice are crucial. This includes unit tests for handlers, domain objects, and integration tests for the slice as a whole. Test files are typically located in a parallel structure under the `tests/` directory (e.g., `tests/Context/Account/Application/UseCase/CreateAccount/CreateAccountCommandHandlerTest.php`).

#### Independence and Shared Kernel

Slices should be designed to be as **independent** as possible. This minimizes the ripple effect of changes; modifying one feature should ideally not impact others.

While striving for self-sufficiency, some common logic, domain models, or infrastructure components might be shared across multiple slices or even bounded contexts. Such shared elements can reside in the `src/Shared` directory. However, the primary goal is for slices to be self-contained units of functionality.

The existing `src/Context/Account/Application/UseCase/CreateAccount` directory and its contents serve as a good reference example of a vertical slice.

### Clean Architecture Layers

Clean Architecture is a software design philosophy that separates elements of a system into distinct layers with specific responsibilities. This approach promotes:
-   **Separation of Concerns:** Different parts of the system are isolated, making them easier to understand, maintain, and evolve.
-   **Testability:** Each layer can be tested independently. Business logic can be tested without UI, database, or external services.
-   **Independence from UI, Frameworks, and Databases:** The core business logic (Domain) is independent of presentation details, specific frameworks, or database technologies. This allows these external parts to be changed with minimal impact on the core logic.

The project primarily follows these three core layers:

#### 1. Domain Layer

-   **Responsibilities:** This is the innermost layer and contains the enterprise-wide business rules, logic, and models. It represents the heart of the application's functionality.
-   **Components:**
  -   **Entities:** Objects with an identity that persists over time (e.g., `Account`).
  -   **Aggregates:** Clusters of entities and value objects treated as a single unit, with one entity acting as the Aggregate Root (e.g., an `Order` aggregate might include `OrderItem` entities).
  -   **Value Objects:** Immutable objects representing descriptive aspects of the domain without a conceptual identity (e.g., `Email`, `Money`).
  -   **Domain Events:** Represent significant occurrences within the domain that other parts of the system might react to (e.g., `AccountCreatedEvent`).
  -   **Repository Interfaces:** Define contracts for data persistence operations, abstracting the actual storage mechanism (e.g., `AccountRepository`).
-   **Characteristics:**
  -   Pure PHP code.
  -   No dependencies on outer layers (Application, Infrastructure). It knows nothing about application logic or how data is stored or presented.
-   **Location:** `src/Context/{BoundedContext}/Domain`

#### 2. Application Layer

-   **Responsibilities:** This layer contains application-specific business rules and orchestrates the use cases of the application. It directs the flow of data and coordinates the Domain layer objects to perform specific tasks.
-   **Components:**
  -   **Commands:** Objects representing an intent to change the system's state (e.g., `CreateAccountCommand`).
  -   **Queries:** Objects representing an intent to retrieve data without altering the system's state (e.g., `GetAccountByIdQuery`).
  -   **Command Handlers:** Process commands, interact with Domain objects (via repository interfaces or directly), and orchestrate the execution of domain logic (e.g., `CreateAccountCommandHandler`).
  -   **Query Handlers:** Process queries, retrieve data (often using repository interfaces), and prepare it for presentation (e.g., `GetAccountByIdQueryHandler`).
  -   **Application Services:** Can be used for tasks that don't fit neatly into a command/query pattern but still represent application-specific operations.
-   **Characteristics:**
  -   Depends on the Domain Layer (it uses Domain entities, events, and repository interfaces).
  -   Must not depend on the Infrastructure Layer directly. It relies on abstractions (interfaces) defined in the Domain layer for external concerns like data persistence.
-   **Location:** `src/Context/{BoundedContext}/Application` (specifically Use Cases within this layer like `src/Context/{BoundedContext}/Application/UseCase/{Action}`).

#### 3. Infrastructure Layer

-   **Responsibilities:** This is the outermost layer and contains implementations for all external concerns. This includes database interactions, connections to external APIs, message queue integrations, framework-specific code, UI components (though UI is often considered a separate concern interacting with Application).
-   **Components:**
  -   **Repository Implementations:** Concrete classes that implement the repository interfaces defined in the Domain Layer, providing data access for a specific technology (e.g., `DoctrineAccountRepository` implementing `AccountRepository`).
  -   **Framework-specific Controllers/Adapters:** Code that bridges requests from the web framework to the Application Layer.
  -   **External Service Clients:** Clients for interacting with third-party APIs.
  -   **Message Queue Producers/Consumers:** Implementations for sending and receiving messages.
-   **Characteristics:**
  -   Implements interfaces defined in the Domain Layer (e.g., `AccountRepository`) or sometimes Application Layer.
  -   Depends on both Domain and Application layers (e.g., a repository implementation will know about Domain entities, and a controller will call Application services/handlers).
  -   **Crucially, no other layer should depend on the Infrastructure layer.** This is achieved through Dependency Inversion (relying on abstractions defined by inner layers).
-   **Location:** `src/Context/{BoundedContext}/Infrastructure`

#### The Dependency Rule

The cornerstone of Clean Architecture is the **Dependency Rule**: *source code dependencies can only point inwards*.
-   The **Domain Layer** has no dependencies on any other layer.
-   The **Application Layer** depends only on the Domain Layer.
-   The **Infrastructure Layer** depends on both the Application and Domain Layers.

This rule ensures that the core business logic (Domain) is protected from changes in external concerns like databases or frameworks.

**Examples from the codebase:**
-   **Domain:** `Account` entity (`src/Context/Account/Domain/Account.php`), `AccountRepository` interface (`src/Context/Account/Domain/AccountRepository.php`).
-   **Application:** `CreateAccountCommand` (`src/Context/Account/Application/UseCase/CreateAccount/CreateAccountCommand.php`), `CreateAccountCommandHandler` (`src/Context/Account/Application/UseCase/CreateAccount/CreateAccountCommandHandler.php`).
-   **Infrastructure:** An example would be `DoctrineAccountRepository.php` (if it existed in `src/Context/Account/Infrastructure/Persistence/DoctrineAccountRepository.php`) implementing `AccountRepository`.

#### Shared Code (`src/Shared`)

The `src/Shared` directory can be used for code that is common across multiple Bounded Contexts. This shared code should also adhere to the Clean Architecture principles:
-   `src/Shared/Domain`: For shared domain models, value objects, or interfaces.
-   `src/Shared/Application`: For shared application services or DTOs (less common, as application logic is usually context-specific).
-   `src/Shared/Infrastructure`: For shared infrastructure components like a generic API client base class or common database connection utilities.

Dependencies between shared modules and context-specific modules must still follow the inward dependency rule.

### Domain-Driven Design (DDD) Principles

Domain-Driven Design (DDD) is an approach to software development that focuses on modeling the software to match a domain according to input from domain experts. It's particularly valuable for complex domains where understanding and managing the business logic is paramount. This project heavily relies on DDD principles.

#### Bounded Contexts

-   **Role:** A Bounded Context is a central pattern in DDD. It defines a specific boundary within which a particular domain model is defined and applicable. Inside a Bounded Context, all terms and concepts of the domain model have a specific meaning. This helps manage complexity by preventing a single, large, and often inconsistent model for the entire system.
-   **Identifying and Defining:** New Bounded Contexts are typically identified by looking for different areas of business concern that use different language or have different rules. For example, "Sales" might be a different Bounded Context from "Support" because the term "Customer" might have different attributes and behaviors in each.
-   **Examples:**
  -   `src/Context/Account`: Manages accounts, balances, and related operations.
  -   `src/Context/User`: Manages user identity, authentication, and authorization.
-   **Configuration:** Each Bounded Context might have its own configuration or definitions. For instance, `src/Context/Account/account.yaml` might define specific parameters or service configurations relevant only to the Account context. Other contexts might have similar files (e.g., `user.yaml` if it existed).

#### Ubiquitous Language

-   **Importance:** This is the practice of crafting a common, rigorous language shared by the development team, domain experts, users, and the code itself. This language should be based on the domain model.
-   **Usage:** The Ubiquitous Language should be used everywhere:
  -   Naming of classes, methods, and variables (e.g., `Account`, `AccountCode`, `debit()`, `credit()`).
  -   In team discussions, documentation, and user stories.
  -   This reduces ambiguity and miscommunication.

#### Aggregates

-   **Definition:** An Aggregate is a cluster of associated domain objects (Entities and Value Objects) that are treated as a single unit for the purpose of data changes.
-   **Aggregate Root (AR):** Each Aggregate has one specific Entity known as the Aggregate Root. The AR is the only member of the Aggregate that outside objects are allowed to hold references to. It acts as a gateway for all modifications within the Aggregate.
  -   The base class for Aggregate Roots in this project is `src/Shared/Domain/AggregateRoot.php`.
-   **Rules for Designing Aggregates:**
  -   **Global Unique ID:** The Aggregate Root must have a globally unique identifier.
  -   **Reference by ID:** Aggregates should reference other Aggregates only by their unique ID, not by holding direct object references. This promotes loose coupling and helps maintain transaction boundaries.
  -   **Transactional Consistency:** Operations on an Aggregate should be atomic. A transaction should not span multiple Aggregates. If business rules require coordination between Aggregates, use eventual consistency mediated by Domain Events.
-   **Example:** The `Account` entity (`src/Context/Account/Domain/Account.php`) is an Aggregate Root. It groups related value objects (like `AccountId`, `AccountCode`, `AccountName`, `AccountBalance`) and ensures its internal consistency.
-   **Invariants:** The Aggregate Root is responsible for enforcing invariants (business rules that must always be true) for all objects within its boundary. For example, an `Account` AR might ensure that its balance never drops below a certain limit.

#### Entities

-   **Definition:** An Entity is an object that is not defined by its attributes, but rather by its thread of continuity and identity. It has a distinct identity that persists through time and different states.
-   **Identity:** The primary concern for an Entity is its unique identifier.
-   **Example:** `src/Context/Account/Domain/Account.php` is an Entity (and also an Aggregate Root in this case). Even if its name or balance changes, it's still the same account because its `AccountId` remains constant.

#### Value Objects

-   **Definition:** A Value Object is an immutable object that represents a descriptive aspect of the domain with no conceptual identity. They are defined by their attributes.
-   **Equality:** Two Value Objects are considered equal if all their constituent attribute values are equal.
-   **Immutability:** Value Objects should be immutable once created. If a change is needed, a new Value Object instance should be created. This makes them safer and easier to reason about.
-   **Examples:**
  -   `src/Context/Account/Domain/ValueObject/AccountId.php`
  -   `src/Context/Account/Domain/ValueObject/AccountCode.php`
  -   `src/Context/Account/Domain/ValueObject/AccountName.php`
  -   Base Value Objects in `src/Shared/Domain/` like `SimpleUuid.php` (often used as a base for ID VOs) and `StringValueObject.php` provide common functionality.

#### Domain Events

-   **Role:** Domain Events are objects that represent something significant that has happened in the domain that domain experts care about. They are a crucial part of decoupling different parts of the domain and enabling eventual consistency.
-   **Naming:** They should be named in the past tense, clearly indicating what occurred (e.g., `AccountWasCreated`, `FundsDebited`).
-   **Creation:** Domain Events are typically created and recorded by Aggregate Roots when their state changes as a result of a command. The `AggregateRoot` base class provides methods to record and pull these events.
-   **Dispatch:** After a transaction is successfully committed, these events are dispatched (e.g., via an Event Bus like `src/Shared/Application/EventBus.php`). Other parts of the system (potentially in different Bounded Contexts) can subscribe to these events and react accordingly (e.g., sending a welcome email when `AccountWasCreated` occurs).
-   **Examples:**
  -   `src/Context/Account/Domain/Bus/AccountWasCreated.php` (Note: Path corrected based on `ls` output)
  -   The base class `src/Shared/Domain/DomainEvent.php` provides common structure for domain events.

#### Repositories

-   **Role:** Repositories are a mechanism for encapsulating storage, retrieval, and search behavior, emulating an in-memory collection of Aggregates. They abstract the underlying data persistence technology.
-   **Definition and Implementation:**
  -   **Interfaces:** Defined in the Domain Layer, alongside the Aggregates they manage (e.g., `src/Context/Account/Domain/AccountRepository.php`). These interfaces form part of the Ubiquitous Language, with methods reflecting domain operations.
  -   **Implementations:** Reside in the Infrastructure Layer, specific to a persistence technology (e.g., `src/Context/Account/Infrastructure/Persistence/Doctrine/DoctrineAccountRepository.php`).
-   **Methods:** Repository methods should be named to reflect domain operations and typically work with Aggregate Roots (e.g., `save(Account $account)`, `findOneByIdOrFail(AccountId $id)`). They should not expose underlying database query language or details.

### CQRS (Command Query Responsibility Segregation) Guidelines

Command Query Responsibility Segregation (CQRS) is an architectural pattern that separates read operations (Queries) from write operations (Commands). This means that the model used to update information (the write model) is different from the model used to read information (the read model).

**Benefits of CQRS:**
-   **Scalability:** Read and write workloads can be scaled independently.
-   **Simplicity:** Models can be simpler; the write model focuses on consistency, while read models are tailored for query needs.
-   **Optimized Data Models:** Read and write sides can use different data models or even different database technologies.

#### Commands

-   **Definition:** An intent to change the system's state.
-   **Naming:** Imperative (e.g., `CreateAccountCommand`).
-   **Characteristics:** Simple DTOs, immutable, carry all necessary data.
-   **Handling:** Processed by a single Command Handler.
-   **Return Value:** Typically none, or only an ID/status.
-   **Examples:** `src/Context/Account/Application/UseCase/CreateAccount/CreateAccountCommand.php`, `src/Shared/Application/Command.php`.

#### Command Handlers

-   **Definition:** Orchestrates command execution.
-   **Workflow:** Validate command -> Retrieve Aggregate(s) -> Execute domain logic on Aggregate(s) -> Persist Aggregate(s) -> Optionally publish events.
-   **Naming:** `{CommandName}Handler` (e.g., `CreateAccountCommandHandler`).
-   **Examples:** `src/Context/Account/Application/UseCase/CreateAccount/CreateAccountCommandHandler.php`, `src/Shared/Application/CommandHandler.php`.

#### Queries

-   **Definition:** A request for data; does not alter system state.
-   **Naming:** Descriptive (e.g., `FindAccountQuery`).
-   **Characteristics:** Simple DTOs, immutable, contain query parameters.
-   **Handling:** Processed by a single Query Handler.
-   **Examples:** `src/Context/Account/Application/UseCase/FindAccount/FindAccountQuery.php`, `src/Shared/Application/Query.php`.

#### Query Handlers

-   **Definition:** Retrieves data for a query.
-   **Workflow:** Directly query data store (primary DB or read model) -> Transform data to DTO/suitable structure.
-   **Naming:** `{QueryName}Handler` (e.g., `FindAccountQueryHandler`).
-   **Examples:** `src/Context/Account/Application/UseCase/FindAccount/FindAccountQueryHandler.php`, `src/Shared/Application/QueryHandler.php`.

#### Read Models (Projections)

-   Specialized, denormalized data structures optimized for specific queries.
-   Built and updated asynchronously by listening to domain events.
-   Allows the read side to be highly performant and tailored.

#### Transaction Management
-   Commands are typically executed within a single transaction.
-   Queries do not need transactions.

#### Eventual Consistency
-   Acknowledge potential delay between command execution and read model updates when using separate, asynchronously updated read models.

### Event Sourcing (ES) Concepts

Event Sourcing (ES) is an architectural pattern where all changes to an application's state are stored as a sequence of immutable events.

**Key Benefits:**
-   Complete audit trail.
-   Ability to reconstruct past states.
-   Temporal queries.
-   Enhanced debugging capabilities.
-   Foundation for diverse projections.

#### Core Concepts in This Project

-   **Domain Events as the Source of Truth (Hybrid Approach):**
  -   Domain Events (e.g., `AccountWasCreated`, `AccountWasEnabled` in `src/Context/Account/Domain/Bus/`) are meticulously recorded.
  -   However, Aggregates like `Account.php` also maintain their current state directly in properties for command processing performance. State is hydrated by the ORM, not purely from events. This is a hybrid approach. Events serve audit, projection, and integration purposes.
-   **Event Structure (`App\Shared\Domain\DomainEvent.php`):**
  -   Standard fields: `aggregateId`, `eventId`, `occurredOn`.
  -   Abstract methods: `eventName()`, `toPrimitives()`, `fromPrimitives()`.
-   **Storing Events:**
  -   Conceptually appended to an Event Stream per aggregate.
  -   Actual storage might be a relational table or a dedicated Event Store.
-   **Reconstructing Aggregate State (Current vs. Pure ES):**
  -   **Current:** `Account.php` does not reconstruct state from events on load; ORM hydrates properties. Methods directly modify properties and then record events.
  -   **Pure ES:** State would be reconstructed by replaying events using `apply()` methods in the aggregate. This is a potential future direction.
-   **Recording Events:**
  -   Aggregates use `record(DomainEvent $event)` from `App\Shared\Domain\AggregateRoot.php`.
  -   Events are pulled via `pullDomainEvents()` and dispatched by infrastructure (e.g., Event Bus after transaction commit).
-   **Projections (Read Models):**
  -   Events are fundamental for building/updating projections via Event Handlers ("Projectors").
-   **Snapshots (Optional Optimization):**
  -   Not currently implemented but a standard ES optimization for long-lived aggregates.
-   **Event Versioning and Schema Evolution:**
  -   Acknowledge as a challenge; strategies include upcasting or maintaining multiple versions.
-   **Idempotency in Event Handlers/Projectors:**
  -   Crucial for "at-least-once" delivery; handlers should tolerate reprocessing events.
-   **Relationship with CQRS:**
  -   ES fits naturally on the command side; events drive read model updates.

## Exhaustive Testing Standards

This section expands upon general testing principles to provide comprehensive guidelines for ensuring all components are robust and reliable. Tests are first-class citizens and must be diligently created and maintained.

### General Testing Philosophy
-   **Goal:** Achieve exhaustive testing to ensure the stability and correctness of every component and business rule.
-   **Maintenance:** Tests must be updated alongside production code. Broken or outdated tests should be treated with the same urgency as production bugs.
-   **Arrange-Act-Assert Pattern:** Structure tests clearly using this pattern.

### Test Organization
-   Tests for a Bounded Context `{BoundedContext}` must reside in `tests/Context/{BoundedContext}/`.
-   The directory structure within `tests/Context/{BoundedContext}/` must mirror the `src/Context/{BoundedContext}/` structure (e.g., `Application/`, `Domain/`, `Infrastructure/`).
-   **Example:** Tests for `src/Context/Account/Application/UseCase/CreateAccount/CreateAccountCommandHandler.php` are located in `tests/Context/Account/Application/UseCase/CreateAccount/CreateAccountCommandHandlerTest.php`.

### Unit Testing Standards

#### Base Test Case for Bounded Contexts
-   Each Bounded Context should provide a base unit test class (e.g., `tests/Context/Account/AccountModuleUnitTestCase.php`).
-   This class should:
  -   Extend a shared base like `MockeryTestCase` if using Mockery extensively.
  -   Set up common mocked dependencies relevant to that context (e.g., `AccountRepository`, `EventBus` are mocked in `AccountModuleUnitTestCase`).
  -   Provide helper methods for common assertions or interactions (e.g., `shouldSave()`, `shouldPublishDomainEvents()` as seen in `AccountModuleUnitTestCase`).
  -   Handle Mockery cleanup in `tearDown()` method (e.g., `Mockery::close()`).
-   **Mandate:** All unit tests for application handlers (Commands/Queries) and domain services within a Bounded Context *must* extend its specific module unit test case.

#### Application Layer Tests (Command/Query Handlers)
-   **Focus:** Test the handler's logic in complete isolation.
-   **Dependencies:** All external dependencies *must* be mocked.
-   **Test Data:** Input data *must* be generated using Object Mothers.
-   **Assertions:**
  -   Verify interactions with mocked dependencies (e.g., repository's `save` method called).
  -   Verify domain events publication.
  -   Assert return values or exceptions.
-   **Example:** `tests/Context/Account/Application/UseCase/CreateAccount/CreateAccountCommandHandlerTest.php`.

#### Domain Layer Tests (Aggregates, Entities)
-   **Focus:** Test business logic, state changes, invariant enforcement, event recording.
-   **Dependencies:** Minimize and mock if any.
-   **Test Data:** Use Object Mothers.
-   **Assertions:**
  -   Verify state initialization and changes.
  -   Verify domain events recorded (via `pullDomainEvents()`).
  -   Verify invariant enforcement (expect domain exceptions).
-   **Example:** `tests/Context/Account/Domain/CreateAccountTest.php`.

#### Value Object Tests
-   **Focus:** Construction, validation, behavior.
-   **Assertions:**
  -   Valid/invalid data construction (expect exceptions for invalid).
  -   Equality logic.
  -   Specific methods.

### Object Mother Pattern
-   **Mandate:** For every significant Entity and Value Object, a corresponding Object Mother *must* be created in `tests/Context/{BoundedContext}/Domain/{ObjectName}Mother.php`.
-   **Structure:** Static `create()` method with sensible defaults, allowing overrides.
-   **Examples:** `AccountMother.php`, `AccountIdMother.php`. Also applicable for Commands/Queries.

### Naming Conventions for Tests
-   **Test Classes:** `{ClassNameUnderTest}Test.php`.
-   **Test Methods:** Descriptive, e.g., `test_it_should_{expected_behavior}_when_{condition}` or `test_{methodName}_{condition}`. (Examples: `CreateAccountCommandHandlerTest::test_it_should_create_an_account()`, `CreateAccountTest::test_it_should_create_an_account_with_valid_data()`).

### "Exhaustive" Testing - What to Cover
-   **Happy Paths:** Expected successful execution flow.
-   **Error Conditions & Exceptions:**
  -   Business rule/invariant violations (expect domain exceptions).
  -   VO validation with invalid data (expect exceptions).
  -   Handler behavior with failing dependencies.
-   **Edge Cases:** Boundary values, null inputs, etc.
-   **Command Handlers:** All command properties used correctly.
-   **Query Handlers:** Different parameter combinations, correct data structure returns.
-   Test all success and failure scenarios.

### Integration Testing Guidelines

-   **Scope:** Interaction between components, especially with infrastructure (database).
-   **Repository Tests:**
  -   Each Doctrine repository implementation *should* have an integration test.
  -   *Must* interact with a real test database.
  -   Verify full lifecycle: persist, retrieve, update, custom queries.
-   **Test Database Setup:**
  -   Separate test database configuration.
  -   Schema managed by migrations.
  -   Strategies for cleaning database between tests (transactions, truncation).
-   **External API Client Tests:** Use PACT or sandbox environments if applicable.

### Code Coverage
-   **Target:** Aim for 85-90%+ for critical Domain/Application logic.
-   **Tools:** PHPUnit's coverage generation. Review reports.
-   **CI Integration:** Consider integrating coverage checks.

## Cross-Cutting Concerns

Cross-cutting concerns are aspects of a program that affect or are used by multiple parts of the system, often spanning across different layers or vertical slices.

### Error Handling and Exception Strategy

-   **Domain Exceptions:**
  -   Represent specific business rule violations; part of Ubiquitous Language.
  -   Defined in `Domain/Exception/` or `Shared/Domain/`.
  -   Thrown by Aggregates, Entities, VOs.
  -   Should have meaningful messages with context.
-   **Application Layer Handling:**
  -   Handlers catch anticipated domain exceptions.
  -   May propagate, translate, or rely on global handlers.
-   **Infrastructure Layer Exceptions:**
  -   Caught by infrastructure components, may be wrapped. Avoid leaking raw infra exceptions.
-   **Global Exception Handling (API):**
  -   `src/Shared/Infrastructure/JsonTransformerExceptionListener.php` for standardized JSON error responses.
  -   Ensure new exceptions are mapped correctly. Consistent error formats.

### Logging

-   **What to Log:** Key application events, errors/exceptions (with context, stack trace, correlation ID), critical external service interactions, security events. Optionally performance/debug info (use with caution).
-   **How to Log:** PSR-3 (Monolog). Inject `LoggerInterface`. Domain objects generally should not log.
-   **Log Levels (PSR-3):** DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY. Use appropriately.
-   **Sensitive Data:** **NEVER** log sensitive PII unless legally compliant and masked/anonymized. Use correlation IDs.

### Configuration Management

-   **Environment Variables:** For environment-specific settings (`.env` files).
-   **YAML Files:** For service definitions, package configs, custom params (`config/`).
-   **Accessing Configuration:** Constructor DI. Avoid global access.
-   **Context-Specific Configuration:** e.g., `src/Context/Account/account.yaml`.

### Security (General Guidelines)

-   **Authentication & Authorization:** Symfony Security component. Roles, permissions, voters.
-   **Input Validation:**
  -   **Application Layer:** Commands/DTOs via Symfony Validator or custom logic.
  -   **Domain Layer:** VOs enforce invariants on construction. Aggregates/Entities enforce business rules.
-   **Data Protection:** Avoid logging PII. Secure secrets (Symfony secrets/env vars). Consider encryption at rest. HTTPS.
-   **Principle of Least Privilege:** Minimum necessary permissions.
-   **Dependency Management:** Keep dependencies updated; monitor vulnerabilities (`symfony security:check`).

This section provides a foundational approach. Specific security measures should be designed based on risk assessment for each feature and data type.

## Final Recommendations

-   **Immutability:** Favor immutable objects where practical (Value Objects, Commands, Queries, Events) to reduce side effects and improve predictability.
-   **Final Classes:** Use `final` for classes that are not designed for extension. This can improve clarity of intent and potentially allow for minor performance optimizations by the PHP engine. This is especially true for Command/Query Handlers, and often Aggregates/Entities unless a specific inheritance strategy is in place.

This comprehensive guide should serve as a valuable resource for the development team.
