# Acme

A minimal Clean Architecture application in PHP: a Hello World home page plus account registration and login,
persisted in PostgreSQL through Doctrine ORM.

## Run

```sh
docker compose up -d --build
```

- App: http://localhost:8000 (override with `APP_PORT`)
- PostgreSQL: `localhost:5433`, user/password/database `acme` (override the port with `DATABASE_PORT`)

Migrations run automatically when the `app` container starts.

## Test

```sh
docker compose exec app composer test              # everything (or `composer test` on the host)
docker compose exec app composer test:unit         # isolated tests only
docker compose exec app composer test:integration  # tests wiring real components together
```

- `src/Tests/Unit`: domain and use cases tested in isolation, with in-memory test doubles (no I/O, no framework).
- `src/Tests/Integration`: the HTTP stack (Slim, Twig, container) and the Doctrine repository against PostgreSQL.
  The Doctrine tests use the `acme_test` database and are skipped when it is not reachable.

## Console

```sh
docker compose exec app bin/console migrations:diff      # generate a migration from the mapping
docker compose exec app bin/console migrations:migrate   # apply migrations
docker compose exec app bin/console list                 # all Doctrine ORM & Migrations commands
```

## Architecture

Dependencies point inward: `Infrastructure` → `Application` → `Domain`.

```
src/
├── Domain/                  Entities, value objects and ports (interfaces), no framework code
│   └── Account/             Account, Email, AccountRepository, PasswordHasher
├── Application/             Use cases orchestrating the domain
│   └── UseCase/             Home, RegisterAccount, Login
├── Infrastructure/
│   ├── Driving/             Adapters calling the application (Slim HTTP controllers, Twig templates, session)
│   └── Driven/              Adapters called by the application (Doctrine repository + XML mapping, migrations, password hashing)
└── Tests/
    ├── Unit/                Isolated tests of the domain and use cases, plus the in-memory doubles they use
    └── Integration/         Tests of real components wired together (HTTP stack, Doctrine + PostgreSQL)
config/container.php         Composition root: binds each port to its adapter (PHP-DI)
```

Controllers never call a use case directly: they send its input (command or query) to the `UseCaseBus`, which runs the
matching use case inside a database transaction (`TransactionalUseCaseBus` + `TransactionManager` port, implemented with
Doctrine in `Driven`). Registering a new use case is one line in `config/container.php`.

Doctrine is mapped with XML files in `Infrastructure/Driven/Persistence/Doctrine/Mapping`, so the domain entities stay
free of persistence concerns.
