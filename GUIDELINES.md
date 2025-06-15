# PHP Project Guidelines

## Project Structure

```
src/
├── Context/
│   └── {BoundedContext}/
│       ├── Application/
│       │   └── UseCase/
│       │       └── {Action}/
│       │           ├── {Action}Query.php
│       │           └── {Action}QueryHandler.php
│       └── Domain/
│           ├── {Entity}.php
│           ├── {Entity}Id.php
│           ├── {Entity}Repository.php
│           └── Exception/
│               └── {Entity}NotFoundException.php
tests/
└── Context/
└── {BoundedContext}/
├── Application/
│   └── UseCase/
│       └── {Action}/
│           └── {Action}QueryHandlerTest.php
└── Domain/
├── {Entity}Mother.php
└── {Entity}IdMother.php
```

## Naming Conventions
- **Classes**: PascalCase (`FindAccountQueryHandler`)
- **Interfaces**: PascalCase (`AccountRepository`)
- **Test classes**: Same as class name + `Test` suffix
- **Test methods**: camelCase with `test` prefix (`testFindAccount`)
- **Object Mothers**: Entity name + `Mother` suffix (`AccountMother`)

## Code Organization
- Use namespaces following folder structure
- Follow CQRS pattern (separate Query/Command objects)
- Implement Repository pattern for data access
- Use Value Objects for primitive values (IDs, emails, etc.)
- Place domain exceptions in `Domain/Exception` folder

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