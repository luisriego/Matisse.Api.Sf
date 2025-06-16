# PHP Project Guidelines

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
            -   *Convention:* `{ErrorDescription}Exception.php`.
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



## Testing Standards
- Organize tests with Arrange-Act-Assert pattern
- Use Mockery for mocking dependencies
- Create Object Mothers for test data generation
- Test both success and failure scenarios
- Use `setUp()` for common test initialization
- Use `tearDown()` for cleanup (Mockery::close())

## Error Handling
- Create specific exception classes for domain errors
- Use meaningful exception messages with context data
- Test exception scenarios explicitly

## Additional Recommendations
- Add type declarations for all properties and method parameters
- Use final classes where inheritance isn't needed
- Consider adding PHPDoc blocks for better documentation
- Implement interfaces for repositories to follow Dependency Inversion
- Use immutable objects when possible

## Vertical Slice Architecture

A **Vertical Slice** represents a single, cohesive feature or use case within the application. Each slice should be self-contained, encompassing all the necessary components to deliver that specific functionality. This approach promotes high cohesion within a slice and low coupling between slices.

The project structure under `src/Context/{BoundedContext}/Application/UseCase/{Action}` is a good example of how a vertical slice is organized.

### Components of a Vertical Slice

A typical vertical slice in this project will include the following components:

- **Command/Query Objects**: These are simple data transfer objects (DTOs) that carry the input data for the use case (e.g., `CreateAccountCommand` or `GetAccountByIdQuery`). They reside within the `{Action}` directory.
- **Command/Query Handlers**: These are the core of the slice, containing the logic to process the command or query. They orchestrate interactions with domain objects and repositories (e.g., `CreateAccountCommandHandler`). They also reside within the `{Action}` directory.
- **Domain Objects**: These are the Aggregates, Entities, and Value Objects relevant to the specific feature. For instance, a `CreateAccount` slice would involve the `Account` aggregate and its associated value objects. These are typically located in the `src/Context/{BoundedContext}/Domain` directory but are directly utilized and often defined by the needs of the slice.
- **Repository Interfaces**: If the slice requires data persistence or retrieval, it will define and use repository interfaces (e.g., `AccountRepository`). These interfaces are part of the Domain layer, typically within `src/Context/{BoundedContext}/Domain`.
- **Domain Events**: If the execution of the slice results in significant state changes that other parts of the system might be interested in, it may raise Domain Events. These are also part of the Domain layer.
- **Infrastructure Implementations**: Concrete implementations of repository interfaces or other external services (like email services, payment gateways) are placed in the `src/Context/{BoundedContext}/Infrastructure` directory (not explicitly shown in the basic structure but implied). While some infrastructure can be shared, a slice might necessitate specific implementations if its requirements are unique.
- **Tests**: Comprehensive tests for all components of the slice are crucial. This includes unit tests for handlers, domain objects, and integration tests for the slice as a whole. Test files are typically located in a parallel structure under the `tests/` directory (e.g., `tests/Context/Account/Application/UseCase/CreateAccount/CreateAccountCommandHandlerTest.php`).

### Independence and Shared Kernel

Slices should be designed to be as **independent** as possible. This minimizes the ripple effect of changes; modifying one feature should ideally not impact others.

While striving for self-sufficiency, some common logic, domain models, or infrastructure components might be shared across multiple slices or even bounded contexts. Such shared elements can reside in the `src/Shared` directory (not explicitly detailed in the basic project structure but a common practice). However, the primary goal is for slices to be self-contained units of functionality.

The existing `src/Context/Account/Application/UseCase/CreateAccount` directory and its contents serve as a good reference example of a vertical slice.

## Clean Architecture Layers

Clean Architecture is a software design philosophy that separates elements of a system into distinct layers with specific responsibilities. This approach promotes:
- **Separation of Concerns:** Different parts of the system are isolated, making them easier to understand, maintain, and evolve.
- **Testability:** Each layer can be tested independently. Business logic can be tested without UI, database, or external services.
- **Independence from UI, Frameworks, and Databases:** The core business logic (Domain) is independent of presentation details, specific frameworks, or database technologies. This allows these external parts to be changed with minimal impact on the core logic.

The project primarily follows these three core layers:

### 1. Domain Layer

- **Responsibilities:** This is the innermost layer and contains the enterprise-wide business rules, logic, and models. It represents the heart of the application's functionality.
- **Components:**
    - **Entities:** Objects with an identity that persists over time (e.g., `Account`).
    - **Aggregates:** Clusters of entities and value objects treated as a single unit, with one entity acting as the Aggregate Root (e.g., an `Order` aggregate might include `OrderItem` entities).
    - **Value Objects:** Immutable objects representing descriptive aspects of the domain without a conceptual identity (e.g., `Email`, `Money`).
    - **Domain Events:** Represent significant occurrences within the domain that other parts of the system might react to (e.g., `AccountCreatedEvent`).
    - **Repository Interfaces:** Define contracts for data persistence operations, abstracting the actual storage mechanism (e.g., `AccountRepository`).
- **Characteristics:**
    - Pure PHP code.
    - No dependencies on outer layers (Application, Infrastructure). It knows nothing about application logic or how data is stored or presented.
- **Location:** `src/Context/{BoundedContext}/Domain`

### 2. Application Layer

- **Responsibilities:** This layer contains application-specific business rules and orchestrates the use cases of the application. It directs the flow of data and coordinates the Domain layer objects to perform specific tasks.
- **Components:**
    - **Commands:** Objects representing an intent to change the system's state (e.g., `CreateAccountCommand`).
    - **Queries:** Objects representing an intent to retrieve data without altering the system's state (e.g., `GetAccountByIdQuery`).
    - **Command Handlers:** Process commands, interact with Domain objects (via repository interfaces or directly), and orchestrate the execution of domain logic (e.g., `CreateAccountCommandHandler`).
    - **Query Handlers:** Process queries, retrieve data (often using repository interfaces), and prepare it for presentation (e.g., `GetAccountByIdQueryHandler`).
    - **Application Services:** Can be used for tasks that don't fit neatly into a command/query pattern but still represent application-specific operations.
- **Characteristics:**
    - Depends on the Domain Layer (it uses Domain entities, events, and repository interfaces).
    - Must not depend on the Infrastructure Layer directly. It relies on abstractions (interfaces) defined in the Domain layer for external concerns like data persistence.
- **Location:** `src/Context/{BoundedContext}/Application` (specifically Use Cases within this layer like `src/Context/{BoundedContext}/Application/UseCase/{Action}`).

### 3. Infrastructure Layer

- **Responsibilities:** This is the outermost layer and contains implementations for all external concerns. This includes database interactions, connections to external APIs, message queue integrations, framework-specific code, UI components (though UI is often considered a separate concern interacting with Application).
- **Components:**
    - **Repository Implementations:** Concrete classes that implement the repository interfaces defined in the Domain Layer, providing data access for a specific technology (e.g., `DoctrineAccountRepository` implementing `AccountRepository`).
    - **Framework-specific Controllers/Adapters:** Code that bridges requests from the web framework to the Application Layer.
    - **External Service Clients:** Clients for interacting with third-party APIs.
    - **Message Queue Producers/Consumers:** Implementations for sending and receiving messages.
- **Characteristics:**
    - Implements interfaces defined in the Domain Layer (e.g., `AccountRepository`) or sometimes Application Layer.
    - Depends on both Domain and Application layers (e.g., a repository implementation will know about Domain entities, and a controller will call Application services/handlers).
    - **Crucially, no other layer should depend on the Infrastructure layer.** This is achieved through Dependency Inversion (relying on abstractions defined by inner layers).
- **Location:** `src/Context/{BoundedContext}/Infrastructure` (this directory might not be explicitly present in the basic structure but is where such implementations would go).

### The Dependency Rule

The cornerstone of Clean Architecture is the **Dependency Rule**: *source code dependencies can only point inwards*.
- The **Domain Layer** has no dependencies on any other layer.
- The **Application Layer** depends only on the Domain Layer.
- The **Infrastructure Layer** depends on both the Application and Domain Layers.

This rule ensures that the core business logic (Domain) is protected from changes in external concerns like databases or frameworks.

**Examples from the codebase:**
- **Domain:** `Account` entity (`src/Context/Account/Domain/Account.php`), `AccountRepository` interface (`src/Context/Account/Domain/AccountRepository.php`).
- **Application:** `CreateAccountCommand` (`src/Context/Account/Application/UseCase/CreateAccount/CreateAccountCommand.php`), `CreateAccountCommandHandler` (`src/Context/Account/Application/UseCase/CreateAccount/CreateAccountCommandHandler.php`).
- **Infrastructure:** An example would be `DoctrineAccountRepository.php` (if it existed in `src/Context/Account/Infrastructure/Persistence/DoctrineAccountRepository.php`) implementing `AccountRepository`.

### Shared Code (`src/Shared`)

The `src/Shared` directory can be used for code that is common across multiple Bounded Contexts. This shared code should also adhere to the Clean Architecture principles:
- `src/Shared/Domain`: For shared domain models, value objects, or interfaces.
- `src/Shared/Application`: For shared application services or DTOs (less common, as application logic is usually context-specific).
- `src/Shared/Infrastructure`: For shared infrastructure components like a generic API client base class or common database connection utilities.

Dependencies between shared modules and context-specific modules must still follow the inward dependency rule.

## Domain-Driven Design (DDD) Principles

Domain-Driven Design (DDD) is an approach to software development that focuses on modeling the software to match a domain according to input from domain experts. It's particularly valuable for complex domains where understanding and managing the business logic is paramount. This project heavily relies on DDD principles.

### Bounded Contexts

- **Role:** A Bounded Context is a central pattern in DDD. It defines a specific boundary within which a particular domain model is defined and applicable. Inside a Bounded Context, all terms and concepts of the domain model have a specific meaning. This helps manage complexity by preventing a single, large, and often inconsistent model for the entire system.
- **Identifying and Defining:** New Bounded Contexts are typically identified by looking for different areas of business concern that use different language or have different rules. For example, "Sales" might be a different Bounded Context from "Support" because the term "Customer" might have different attributes and behaviors in each.
- **Examples:**
    - `src/Context/Account`: Manages accounts, balances, and related operations.
    - `src/Context/User`: Manages user identity, authentication, and authorization.
- **Configuration:** Each Bounded Context might have its own configuration or definitions. For instance, `src/Context/Account/account.yaml` might define specific parameters or service configurations relevant only to the Account context. Other contexts might have similar files (e.g., `user.yaml` if it existed).

### Ubiquitous Language

- **Importance:** This is the practice of crafting a common, rigorous language shared by the development team, domain experts, users, and the code itself. This language should be based on the domain model.
- **Usage:** The Ubiquitous Language should be used everywhere:
    - Naming of classes, methods, and variables (e.g., `Account`, `AccountCode`, `debit()`, `credit()`).
    - In team discussions, documentation, and user stories.
    - This reduces ambiguity and miscommunication.

### Aggregates

- **Definition:** An Aggregate is a cluster of associated domain objects (Entities and Value Objects) that are treated as a single unit for the purpose of data changes.
- **Aggregate Root (AR):** Each Aggregate has one specific Entity known as the Aggregate Root. The AR is the only member of the Aggregate that outside objects are allowed to hold references to. It acts as a gateway for all modifications within the Aggregate.
    - The base class for Aggregate Roots in this project is `src/Shared/Domain/AggregateRoot.php`.
- **Rules for Designing Aggregates:**
    - **Global Unique ID:** The Aggregate Root must have a globally unique identifier.
    - **Reference by ID:** Aggregates should reference other Aggregates only by their unique ID, not by holding direct object references. This promotes loose coupling and helps maintain transaction boundaries.
    - **Transactional Consistency:** Operations on an Aggregate should be atomic. A transaction should not span multiple Aggregates. If business rules require coordination between Aggregates, use eventual consistency mediated by Domain Events.
- **Example:** The `Account` entity (`src/Context/Account/Domain/Account.php`) is an Aggregate Root. It groups related value objects (like `AccountId`, `AccountCode`, `AccountName`, `AccountBalance`) and ensures its internal consistency.
- **Invariants:** The Aggregate Root is responsible for enforcing invariants (business rules that must always be true) for all objects within its boundary. For example, an `Account` AR might ensure that its balance never drops below a certain limit.

### Entities

- **Definition:** An Entity is an object that is not defined by its attributes, but rather by its thread of continuity and identity. It has a distinct identity that persists through time and different states.
- **Identity:** The primary concern for an Entity is its unique identifier.
- **Example:** `src/Context/Account/Domain/Account.php` is an Entity (and also an Aggregate Root in this case). Even if its name or balance changes, it's still the same account because its `AccountId` remains constant.

### Value Objects

- **Definition:** A Value Object is an immutable object that represents a descriptive aspect of the domain with no conceptual identity. They are defined by their attributes.
- **Equality:** Two Value Objects are considered equal if all their constituent attribute values are equal.
- **Immutability:** Value Objects should be immutable once created. If a change is needed, a new Value Object instance should be created. This makes them safer and easier to reason about.
- **Examples:**
    - `src/Context/Account/Domain/ValueObject/AccountId.php`
    - `src/Context/Account/Domain/ValueObject/AccountCode.php`
    - `src/Context/Account/Domain/ValueObject/AccountName.php`
    - Base Value Objects in `src/Shared/Domain/` like `SimpleUuid.php` (often used as a base for ID VOs) and `StringValueObject.php` provide common functionality.

### Domain Events

- **Role:** Domain Events are objects that represent something significant that has happened in the domain that domain experts care about. They are a crucial part of decoupling different parts of the domain and enabling eventual consistency.
- **Naming:** They should be named in the past tense, clearly indicating what occurred (e.g., `AccountWasCreated`, `FundsDebited`).
- **Creation:** Domain Events are typically created and recorded by Aggregate Roots when their state changes as a result of a command. The `AggregateRoot` base class provides methods to record and pull these events.
- **Dispatch:** After a transaction is successfully committed, these events are dispatched (e.g., via an Event Bus like `src/Shared/Application/EventBus.php`). Other parts of the system (potentially in different Bounded Contexts) can subscribe to these events and react accordingly (e.g., sending a welcome email when `AccountWasCreated` occurs).
- **Examples:**
    - `src/Context/Account/Domain/Event/AccountWasCreated.php`
    - The base class `src/Shared/Domain/DomainEvent.php` provides common structure for domain events.

### Repositories

- **Role:** Repositories are a mechanism for encapsulating storage, retrieval, and search behavior, emulating an in-memory collection of Aggregates. They abstract the underlying data persistence technology.
- **Definition and Implementation:**
    - **Interfaces:** Defined in the Domain Layer, alongside the Aggregates they manage (e.g., `src/Context/Account/Domain/AccountRepository.php`). These interfaces form part of the Ubiquitous Language, with methods reflecting domain operations.
    - **Implementations:** Reside in the Infrastructure Layer, specific to a persistence technology (e.g., `src/Context/Account/Infrastructure/Persistence/DoctrineAccountRepository.php`).
- **Methods:** Repository methods should be named to reflect domain operations and typically work with Aggregate Roots (e.g., `save(Account $account)`, `findOneByIdOrFail(AccountId $id)`). They should not expose underlying database query language or details.

## CQRS (Command Query Responsibility Segregation) Guidelines

Command Query Responsibility Segregation (CQRS) is an architectural pattern that separates read operations (Queries) from write operations (Commands). This means that the model used to update information (the write model) is different from the model used to read information (the read model). The existing note in "Code Organization" to "Follow CQRS pattern (separate Query/Command objects)" is expanded here.

**Benefits of CQRS:**
- **Scalability:** Read and write workloads can be scaled independently. For many applications, reads are far more frequent than writes.
- **Simplicity:** Models can be simpler. The write model focuses only on processing commands and enforcing consistency, while read models can be tailored for specific query needs.
- **Optimized Data Models:** The read side can use data models (even different databases or storage mechanisms) optimized for querying, while the write side uses a model optimized for transactional consistency and domain logic (typically the Aggregates from DDD).

### Commands

- **Definition:** A Command represents an intent to change the state of the system. It encapsulates all the information needed to perform a specific action.
- **Naming:** Should be named imperatively and clearly describe the intent (e.g., `CreateAccountCommand`, `UpdateUserAddressCommand`, `DisableProductCommand`).
- **Characteristics:**
    - **Simple DTOs:** They are simple data transfer objects, primarily holding data. They should not contain business logic.
    - **All Necessary Data:** A command should carry all the data required by the command handler to execute the action, avoiding the need for the handler to fetch additional data before processing.
    - **Immutability:** Commands should be immutable once created.
- **Handling:** Processed by a single Command Handler.
- **Return Value:** Typically, commands should not return data. Their execution might result in an exception if something goes wrong, or they might return a simple acknowledgement (like an ID of a created resource or a status). Returning the full updated resource is generally discouraged as it blurs the line with queries.
- **Examples:**
    - `src/Context/Account/Application/UseCase/CreateAccount/CreateAccountCommand.php`
    - Base class: `src/Shared/Application/Command.php`

### Command Handlers

- **Definition:** A Command Handler is responsible for processing a single type of command. It orchestrates the execution of the command's intent.
- **Typical Workflow:**
    1. **Validation:** The handler (or an earlier stage like a Value Object constructor within the Command DTO) validates the command's input data. This can involve checking formats, required fields, or simple business rules that don't require fetching domain state.
    2. **Retrieve Aggregate(s):** It fetches the relevant Aggregate Root(s) from a repository using the ID(s) provided in the command.
    3. **Execute Domain Logic:** It calls methods on the Aggregate Root(s) to perform the requested action. The Aggregate is responsible for enforcing its invariants (complex business rules).
    4. **Persist State:** It uses the repository to save the modified Aggregate(s). The repository handles the actual data persistence.
    5. **Publish Domain Events (Optional):** Domain events recorded by the Aggregate during its operations are typically dispatched. This dispatch might be handled by the repository, an event bus, or application-level infrastructure after the transaction commits.
- **Naming:** Conventionally named as `{CommandName}Handler` (e.g., `CreateAccountCommandHandler`).
- **Examples:**
    - `src/Context/Account/Application/UseCase/CreateAccount/CreateAccountCommandHandler.php`
    - Base class/interface: `src/Shared/Application/CommandHandler.php`

### Queries

- **Definition:** A Query represents a request for data. It reads the state of the system without altering it.
- **Naming:** Should be named descriptively, often starting with "Find", "Get", or "List" to indicate a read operation (e.g., `FindAccountByIdQuery`, `ListActiveUsersQuery`).
- **Characteristics:**
    - **Simple DTOs:** They are simple data transfer objects containing the parameters for the data retrieval (e.g., an ID, filter criteria, pagination info).
    - **Immutability:** Queries should be immutable once created.
- **Handling:** Processed by a single Query Handler.
- **Examples:**
    - `src/Context/Account/Application/UseCase/FindAccount/FindAccountQuery.php`
    - Base class: `src/Shared/Application/Query.php`

### Query Handlers

- **Definition:** A Query Handler is responsible for processing a single type of query and returning the requested data.
- **Typical Workflow:**
    1. **Data Retrieval:** The handler directly queries a data store. This could be the primary database (e.g., using Doctrine ORM for simple queries) or a dedicated, optimized read model for more complex scenarios. Query handlers should bypass Aggregates and domain logic for reads if possible, directly accessing the data representation.
    2. **Data Transformation:** It transforms the raw data retrieved from the store into a Data Transfer Object (DTO), a simple array, or a structure suitable for the client (e.g., a view model for a UI).
- **Naming:** Conventionally named as `{QueryName}Handler` (e.g., `FindAccountQueryHandler`).
- **Examples:**
    - `src/Context/Account/Application/UseCase/FindAccount/FindAccountQueryHandler.php`
    - Base class/interface: `src/Shared/Application/QueryHandler.php`

### Read Models (Projections)

For applications with complex querying needs or those using Event Sourcing, dedicated **Read Models** (also known as projections) are highly beneficial.
- These are specialized data structures optimized for specific queries.
- They are built and updated asynchronously by listening to domain events published by the write side.
- For example, an `OrderPlaced` event might trigger an update to a read model that stores `CustomerOrderSummaries`.
- This allows the read side to be highly performant and tailored, without impacting the write model's focus on transactional consistency. (Further details on building these can be discussed in an Event Sourcing section).

### Benefits of CQRS Separation

- **Focused Models:** The write side (Commands, Command Handlers, Aggregates) can focus purely on domain logic, business rule enforcement, and transactional consistency.
- **Optimized Reads:** The read side (Queries, Query Handlers, Read Models) can be independently optimized for performance, using denormalized data, different database technologies, or caching strategies tailored to specific query use cases.

### Transaction Management

- **Commands:** Command execution is typically performed within a single atomic transaction. If any part of the operation fails (e.g., an invariant is violated in the Aggregate, or the database save fails), the entire operation is rolled back to maintain consistency.
- **Queries:** Queries, by definition, do not alter system state and therefore do not require transactions. They should be idempotent.

### Eventual Consistency

When using separate read models that are updated asynchronously from domain events, it's important to acknowledge the concept of **Eventual Consistency**.
- This means there might be a brief delay between when a command is processed (and state is changed on the write side) and when that change is reflected in the read models.
- For many systems, this slight delay is acceptable and is a trade-off for the benefits of scalability and performance. The UI should be designed to handle this if necessary (e.g., by showing optimistic updates or indicating when data is still processing).

## Event Sourcing (ES) Concepts

Event Sourcing (ES) is an architectural pattern where all changes to an application's state are stored as a sequence of immutable events. Instead of persisting the current state of an entity directly, we persist the full history of events that have affected that entity.

**Key Benefits of Event Sourcing:**
- **Audit Trail:** A complete, immutable history of all changes is available, which is invaluable for auditing, debugging, and understanding system behavior over time.
- **Reconstruct Past States:** The state of an aggregate can be reconstructed at any point in time by replaying events up to that point.
- **Temporal Queries:** Enables querying how the state of an entity evolved.
- **Debugging:** Makes it easier to reproduce errors by replaying the exact sequence of events that led to a problematic state.
- **Diverse Projections:** Events can be used to build multiple, diverse read models (projections) tailored to different query needs, without affecting the write model.

### Core Concepts in This Project

#### 1. Domain Events as the Source of Truth (with a caveat)

In an ideal Event Sourcing system, Domain Events are the absolute source of truth. The current state of an aggregate is derived solely from its history of events.

In this project, we adopt a hybrid approach:
- **Domain Events are meticulously recorded:** Events like `AccountWasCreated` (if it exists in `src/Context/Account/Domain/Bus/`), `AccountWasEnabled`, and `AccountWasDisabled` (from `src/Context/Account/Domain/Bus/`) capture significant business occurrences. These are crucial for auditing, integration, and potentially for building read-model projections.
- **Aggregates also maintain current state directly:** As seen in `Account.php`, the aggregate root maintains its current state in properties (e.g., `$code`, `$name`, `$isActive`). This state is likely hydrated directly by the ORM (Doctrine) for command processing.
- **Why this hybrid approach?** This can offer performance benefits for command processing as the aggregate's state is immediately available without replaying events. Events are then used for other purposes (audit, projections, integration). A move to "pure" ES (where state is *only* in events and reconstructed on load) could be a future direction if deemed necessary.

#### 2. Event Structure

Domain events adhere to a common structure, typically extending `App\Shared\Domain\DomainEvent.php`.
- **Standard Fields:**
    - `aggregateId`: The ID of the aggregate instance the event belongs to.
    - `eventId`: A unique ID for the event itself.
    - `occurredOn`: A timestamp indicating when the event happened.
    - `eventName()`: A static method returning the type of the event (e.g., "AccountWasCreated").
- **Payload:** The specific data associated with the event is contained within the event class's properties and handled by its `toPrimitives()` method for serialization.
- **Serialization:**
    - `toPrimitives()`: Converts the event object into a plain PHP array for storage or transmission.
    - `fromPrimitives()`: A static factory method to reconstruct an event object from its primitive (array) representation.
- **Example:** `AccountWasEnabled` (in `src/Context/Account/Domain/Bus/`) would extend `DomainEvent` and include its specific payload relevant to an account being enabled.

#### 3. Storing Events

While the guidelines don't mandate a specific Event Store technology:
- Conceptually, events are appended to an **Event Stream** for each aggregate instance (e.g., all events for a specific `AccountId`).
- The actual storage mechanism might currently be a relational database table (e.g., managed by Doctrine) where serialized events are stored. A dedicated Event Store database is also an option for more advanced ES implementations.

#### 4. Reconstructing Aggregate State (Current Approach vs. Pure ES)

- **Pure ES Approach:** In a pure ES system, an aggregate's state is reconstructed by loading all its events from the stream and applying them one by one in order. The aggregate would have methods like `apply(DomainEvent $event)` or specific handlers like `applyAccountWasCreated(AccountWasCreated $event)` that modify its state based on the event's data.
- **Current Project Approach (`Account.php`):**
    - The `Account` aggregate **does not reconstruct its state from events upon loading**. Its state is directly hydrated by the ORM from database columns.
    - When a command method like `enable()` is called on `Account.php`:
        1. The aggregate's state properties are directly modified (e.g., `$this->isActive = true;`).
        2. An event (e.g., `AccountWasEnabled`) is then recorded using the `record()` method.
    - This means the properties serve as the primary source of state for command execution logic within the aggregate, while the recorded events serve other purposes.

#### 5. Recording Events

- Aggregates use the `record(DomainEvent $event)` method inherited from `App\Shared\Domain\AggregateRoot.php`.
- This method adds the event to an internal list within the aggregate.
- After a command handler successfully processes a command and calls the repository to save the aggregate, these recorded events are typically retrieved using `pullDomainEvents()` (also from `AggregateRoot.php`) and then dispatched by an Event Bus or persisted to an event store by the infrastructure layer.

### Projections (Read Models)

Events are fundamental for building and updating projections (read models) on the query side of CQRS.
- **Definition:** Projections are denormalized views of data, specifically designed and optimized for particular query requirements.
- **Mechanism:**
    - Dedicated **Event Handlers** (often called "Projectors") subscribe to specific types of domain events.
    - When an event of interest occurs, the projector processes the event data and updates the corresponding read model(s).
- **Example:**
    - If an `AccountBalanceProjection` existed, it might listen for events like `TransactionCreditedToAccount` and `TransactionDebitedFromAccount`.
    - Upon receiving `TransactionCreditedToAccount`, the projector would update the balance in its dedicated read model table for that account. This avoids loading the full `Account` aggregate for balance queries. (Note: This is a generic example; specific events from the project like `AccountTransactionRecorded` would be used if available).

### Snapshots (Optional Optimization)

- For aggregates with very long event streams (many thousands of events), replaying all events every time the aggregate is loaded can become a performance bottleneck.
- **Snapshots** are an optimization where the aggregate's full state is saved at a specific event version (e.g., every 100 events).
- To rehydrate, the system loads the latest snapshot and then replays only the events that occurred after that snapshot.
- This project does not currently explicitly mention or show evidence of using snapshots in `Account.php` or `AggregateRoot.php`, but it's a standard ES optimization to be aware of for future scalability.

### Event Versioning and Schema Evolution

- A significant challenge in long-running ES systems is that the structure (schema) of events may need to change over time.
- **Strategies (brief mention):**
    - **Upcasting:** Transforming older versions of an event into the current version on-the-fly when an event is read from the store.
    - **Copy and Transform:** Migrating events to a new schema during a maintenance window.
    - Maintaining multiple versions of event handlers.
- This is an advanced topic; for now, the guideline is to be mindful that event schemas are not set in stone indefinitely.

### Idempotency in Event Handlers/Projectors

- Event handlers and projectors should be designed to be **idempotent**.
- This means that processing the same event multiple times should not result in incorrect data or unintended side effects. This is crucial if the event delivery mechanism guarantees "at-least-once" delivery, as events might be re-delivered.
- Techniques include tracking processed event IDs or designing updates to be inherently idempotent (e.g., using UPSERT operations).

### Relationship with CQRS

- Event Sourcing is a natural fit for the **command side** of CQRS. Aggregates process commands and produce events as the result of state changes.
- These events are then used to update **read models** on the query side, often asynchronously. This reinforces the separation of read and write concerns.

## Exhaustive Testing Standards

This section expands upon the initial "Testing Standards" to provide comprehensive guidelines for ensuring all components are robust and reliable. Tests are first-class citizens in this project and must be diligently created and maintained.

### General Testing Philosophy
- **Goal:** Achieve exhaustive testing to ensure the stability and correctness of every component and business rule.
- **Maintenance:** Tests must be updated alongside production code. Broken or outdated tests should be treated with the same urgency as production bugs.

### Test Organization
- Tests for a Bounded Context `{BoundedContext}` must reside in `tests/Context/{BoundedContext}/`.
- The directory structure within `tests/Context/{BoundedContext}/` must mirror the `src/Context/{BoundedContext}/` structure (e.g., `Application/`, `Domain/`, `Infrastructure/`).
- **Example:** Tests for `src/Context/Account/Application/UseCase/CreateAccount/CreateAccountCommandHandler.php` are located in `tests/Context/Account/Application/UseCase/CreateAccount/CreateAccountCommandHandlerTest.php`.

### Unit Testing Standards

#### Base Test Case for Bounded Contexts
- Each Bounded Context should provide a base unit test class, extending a shared base if necessary (e.g., `MockeryTestCase` or a project-specific one). For instance, the Account context uses `tests/Context/Account/AccountModuleUnitTestCase.php`.
- This context-specific base class should:
    - Set up common mocked dependencies relevant to that context (e.g., `AccountRepository`, `EventBus` are mocked in `AccountModuleUnitTestCase`).
    - Provide helper methods for common assertions or interactions (e.g., `shouldSave()`, `shouldPublishDomainEvents()` as seen in `AccountModuleUnitTestCase`).
- **Mandate:** All unit tests for application handlers (Commands/Queries) and domain services within a Bounded Context *must* extend its specific module unit test case (e.g., `AccountModuleUnitTestCase.php`).

#### Application Layer Tests (Command/Query Handlers)
- **Focus:** Test the handler's logic in complete isolation from external dependencies.
- **Dependencies:** All external dependencies (Repositories, EventBus, other Application or Infrastructure services) *must* be mocked. PHPUnit's mocking capabilities, often initialized in the context-specific base test case's `setUp()` method, should be used.
- **Test Data:** Input data for Commands and Queries *must* be generated using Object Mothers (e.g., `AccountIdMother::create()`, `AccountCodeMother::create()`, `CreateAccountCommandMother::create()`).
- **Assertions:**
    - Verify interactions with mocked dependencies. Use helper methods like `shouldSave($this->repository)` from `AccountModuleUnitTestCase` to assert that a repository's `save` method was called with the expected aggregate.
    - Verify that domain events are (or are not) published as expected using helpers like `shouldPublishDomainEvents($this->eventBus, $expectedEventsArray)`.
    - Assert any direct return values or exceptions thrown by the handler.
- **Example:** `tests/Context/Account/Application/UseCase/CreateAccount/CreateAccountCommandHandlerTest.php` demonstrates setting up a command, defining expected aggregate state or events, and then asserting interactions and outcomes.

#### Domain Layer Tests (Aggregates, Entities)
- **Focus:** Test the core business logic, state changes, invariant enforcement, and event recording within domain objects (Aggregates and Entities).
- **Dependencies:** Domain objects should ideally have minimal to no external dependencies. If any exist (e.g., a domain service for complex calculations), they should be mocked.
- **Test Data:** Use Object Mothers to create instances of Aggregates, Entities, or their constituent Value Objects (e.g., `AccountMother::create()`, `AccountIdMother::create()`).
- **Assertions:**
    - Verify correct state initialization through factory methods or constructors (e.g., `Account::create()`).
    - Verify that methods on the Aggregate/Entity correctly change its state.
    - Verify that appropriate domain events are recorded after performing actions (e.g., after calling `account->enable()`, check `account->pullDomainEvents()` for an `AccountWasEnabled` event).
    - Verify that all business rules and invariants are correctly enforced (e.g., attempting an invalid state transition should throw a specific domain exception).
- **Example:** `tests/Context/Account/Domain/CreateAccountTest.php` likely tests the `Account::create()` factory method, ensuring initial state and events. `tests/Context/Account/Domain/EnableAccountTest.php` would test the `enable()` method.

#### Value Object Tests
- **Focus:** Test the construction, validation, and behavior of individual Value Objects.
- **Test Data:** Use direct instantiation or specific Value Object Mothers if complexity warrants.
- **Assertions:**
    - Test construction with valid data.
    - Test construction with invalid data, ensuring appropriate domain exceptions (e.g., `InvalidArgumentException` or custom exceptions) are thrown.
    - Test equality logic (e.g., `$vo1->equals($vo2)`).
    - Test any other specific methods or behaviors of the Value Object.
- **Example:** The existence of `AccountCodeMother.php` implies tests for `AccountCode.php` validating its format.

### Object Mother Pattern
- **Mandate:** For every significant Entity and Value Object in the Domain layer, a corresponding Object Mother *must* be created in `tests/Context/{BoundedContext}/Domain/{ObjectName}Mother.php`.
- **Structure:**
    - Mothers should provide a static `create()` method that constructs a valid instance with sensible default values.
    - Mothers should allow overriding of these default values by accepting optional parameters in the `create()` method to cater to specific test scenarios.
- **Examples:** `tests/Context/Account/Domain/AccountMother.php`, `AccountIdMother.php`, `AccountCodeMother.php`, `AccountNameMother.php`.
- For Commands and Queries, Object Mothers can also be created (e.g., `CreateAccountCommandMother.php`) to simplify test setup.

### Naming Conventions for Tests
- **Test Classes:** Suffix the class name with `Test` (e.g., `CreateAccountCommandHandlerTest.php`, `AccountTest.php`).
- **Test Methods:** Use descriptive names that indicate the tested unit, the condition/scenario, and the expected outcome. Current project examples show:
    - `test_it_should_{expected_behavior}_when_{condition}` (e.g., `CreateAccountCommandHandlerTest::test_it_should_create_an_account()`, `CreateAccountTest::test_it_should_create_an_account_with_valid_data()`).
    - Or more simply, `test_{method_being_tested}_{outcome_or_condition}`.
    - Strive for clarity and expressiveness.

### "Exhaustive" Testing - What to Cover
- **Happy Paths:** Test the expected successful execution flow for every public method in Application Handlers, Aggregates, Entities, and Domain Services.
- **Error Conditions & Exceptions:**
    - For every business rule and invariant defined in an Aggregate or Entity, write a specific test that attempts to violate it and asserts that the correct domain exception is thrown.
    - For Value Objects, test all validation rules by attempting to construct them with invalid data, asserting the expected exceptions.
    - Test Application Handlers for scenarios where dependencies (e.g., repository finding an entity) might fail or return unexpected results, if applicable.
- **Edge Cases:** Consider boundary values (min/max for numbers, empty/long strings), null inputs (if permissible), and other unusual but valid inputs.
- **Command Handlers:** Verify that all properties from the Command are correctly used and mapped to domain object interactions.
- **Query Handlers:** Test different combinations of query parameters. Verify the structure and content of the returned DTOs or data.

### Integration Testing Guidelines

- **Scope:** Focus on testing the interaction between components, particularly with infrastructure like the database or external APIs.
- **Repository Tests:**
    - Each Doctrine repository implementation (e.g., `src/Context/Account/Infrastructure/Persistence/DoctrineAccountRepository.php`) *should* have a corresponding integration test class (e.g., `tests/Context/Account/Infrastructure/Persistence/DoctrineAccountRepositoryTest.php`).
    - These tests *must* interact with a real test database, not a mock.
    - Verify the full lifecycle:
        - Persisting an aggregate (`save()`).
        - Retrieving the aggregate by its ID (`findOneByIdOrFail()`) and asserting its data integrity.
        - Updating an aggregate and re-retrieving it.
        - Testing any custom query methods in the repository.
- **Test Database Setup:**
    - The test environment should be configured to use a separate test database (e.g., via environment variables in `phpunit.xml` or `.env.test`).
    - Database schema should be managed by migrations (e.g., `doctrine:migrations:migrate`).
    - Consider strategies for cleaning the database between tests (e.g., transactions, truncation) to ensure test isolation.
- **External API Client Tests (if applicable):**
    - If the system integrates with external APIs via clients in the Infrastructure layer, these should also have integration tests. These might use tools like PACT for consumer-driven contract testing or connect to sandbox environments of the external APIs if available and reliable.

### Code Coverage
- **Target:** Aim for a high code coverage percentage (e.g., 85-90% or higher) for critical Domain and Application layer logic. Infrastructure layer tests will contribute but might have lower targets if they are primarily simple data mappings.
- **Tools:** Utilize PHPUnit's built-in code coverage generation capabilities (e.g., `--coverage-html`, `--coverage-clover`). Review coverage reports to identify untested code paths.
- **CI Integration:** Consider integrating coverage checks into the Continuous Integration pipeline to maintain standards.

