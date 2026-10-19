# Acme

A minimal Clean Architecture application in PHP: a Hello World home page, account registration and login, and a small
patient registry where each account manages its own patients. Everything is persisted in PostgreSQL through Doctrine ORM.

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
├── Domain/                  Entities, value objects and business rules, no framework code
│   ├── Account/             Account, Email
│   └── Patient/             Patient, PersonName, Gender, BirthDate
├── Application/             Use cases orchestrating the domain
│   ├── Ports/               Interfaces the use cases need, implemented in Driven (AccountRepository, PatientRepository,
│   │                        PasswordHasher)
│   ├── Bus/                 UseCaseBus, the single entry point to the use cases
│   ├── Transaction/         TransactionManager port, used by the bus
│   └── UseCase/             Home, RegisterAccount, Login,
│                            ListPatients, GetPatient, CreatePatient, UpdatePatient, DeletePatient
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

Each patient belongs to the account that created it (`Patient::ownerId()`): `PatientRepository` only looks patients up
for a given owner, so a use case can never reach another account's patient, which is reported as not found (404). The
`/patients` routes require a logged-in account (`RequireAuthentication` middleware), and the HTML forms reach the PUT and
DELETE routes through a hidden `_METHOD` field (Slim's `MethodOverrideMiddleware`).

Doctrine is mapped with XML files in `Infrastructure/Driven/Persistence/Doctrine/Mapping`, so the domain entities stay
free of persistence concerns.
