<?php

declare(strict_types=1);

use Acme\Application\Bus\DirectUseCaseBus;
use Acme\Application\Bus\TransactionalUseCaseBus;
use Acme\Application\Bus\UseCaseBus;
use Acme\Application\Ports\AccountRepository;
use Acme\Application\Ports\PasswordHasher;
use Acme\Application\Ports\PatientRepository;
use Acme\Application\Transaction\TransactionManager;
use Acme\Application\UseCase\CreatePatient\CreatePatientCommand;
use Acme\Application\UseCase\CreatePatient\CreatePatientUseCase;
use Acme\Application\UseCase\DeletePatient\DeletePatientCommand;
use Acme\Application\UseCase\DeletePatient\DeletePatientUseCase;
use Acme\Application\UseCase\GetPatient\GetPatientQuery;
use Acme\Application\UseCase\GetPatient\GetPatientUseCase;
use Acme\Application\UseCase\Home\HomeQuery;
use Acme\Application\UseCase\Home\HomeUseCase;
use Acme\Application\UseCase\ListPatients\ListPatientsQuery;
use Acme\Application\UseCase\ListPatients\ListPatientsUseCase;
use Acme\Application\UseCase\Login\LoginCommand;
use Acme\Application\UseCase\Login\LoginUseCase;
use Acme\Application\UseCase\RegisterAccount\RegisterAccountCommand;
use Acme\Application\UseCase\RegisterAccount\RegisterAccountUseCase;
use Acme\Application\UseCase\UpdatePatient\UpdatePatientCommand;
use Acme\Application\UseCase\UpdatePatient\UpdatePatientUseCase;
use Acme\Infrastructure\Driven\Persistence\Doctrine\DoctrineAccountRepository;
use Acme\Infrastructure\Driven\Persistence\Doctrine\DoctrinePatientRepository;
use Acme\Infrastructure\Driven\Persistence\Doctrine\DoctrineTransactionManager;
use Acme\Infrastructure\Driven\Persistence\Doctrine\EntityManagerFactory;
use Acme\Infrastructure\Driven\Security\NativePasswordHasher;
use Acme\Infrastructure\Driving\Http\HttpApplication;
use Acme\Infrastructure\Driving\Http\Session\PhpSession;
use Acme\Infrastructure\Driving\Http\Session\Session;
use DI\ContainerBuilder;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Views\Twig;

use function DI\autowire;

/**
 * Composition root: binds every port to its adapter. Pass $overrides to swap adapters (e.g. in tests).
 *
 * @param array<string, mixed> $overrides
 */
return static function (array $overrides = []): ContainerInterface {
    $builder = new ContainerBuilder();

    $builder->addDefinitions([
        'app.debug' => static fn (): bool => filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOL),
        'app.log_errors' => true,
        'database.url' => static fn (): string => getenv('DATABASE_URL')
            ?: 'postgresql://acme:acme@127.0.0.1:5433/acme?serverVersion=18&charset=utf8',

        // Application: every use case is reached through the bus, which runs it in a transaction
        UseCaseBus::class => static fn (ContainerInterface $c): UseCaseBus => new TransactionalUseCaseBus(
            new DirectUseCaseBus([
                HomeQuery::class => static fn (): HomeUseCase => $c->get(HomeUseCase::class),
                RegisterAccountCommand::class => static fn (): RegisterAccountUseCase => $c->get(RegisterAccountUseCase::class),
                LoginCommand::class => static fn (): LoginUseCase => $c->get(LoginUseCase::class),
                ListPatientsQuery::class => static fn (): ListPatientsUseCase => $c->get(ListPatientsUseCase::class),
                GetPatientQuery::class => static fn (): GetPatientUseCase => $c->get(GetPatientUseCase::class),
                CreatePatientCommand::class => static fn (): CreatePatientUseCase => $c->get(CreatePatientUseCase::class),
                UpdatePatientCommand::class => static fn (): UpdatePatientUseCase => $c->get(UpdatePatientUseCase::class),
                DeletePatientCommand::class => static fn (): DeletePatientUseCase => $c->get(DeletePatientUseCase::class),
            ]),
            $c->get(TransactionManager::class),
        ),

        // Driven
        TransactionManager::class => autowire(DoctrineTransactionManager::class),
        EntityManagerInterface::class => static fn (ContainerInterface $c): EntityManagerInterface => EntityManagerFactory::create(
            $c->get('database.url'),
            $c->get('app.debug'),
        ),
        AccountRepository::class => autowire(DoctrineAccountRepository::class),
        PatientRepository::class => autowire(DoctrinePatientRepository::class),
        PasswordHasher::class => autowire(NativePasswordHasher::class),
        DependencyFactory::class => static fn (ContainerInterface $c): DependencyFactory => DependencyFactory::fromEntityManager(
            new ConfigurationArray([
                'migrations_paths' => [
                    'Acme\Infrastructure\Driven\Persistence\Doctrine\Migrations' => dirname(__DIR__) . '/src/Infrastructure/Driven/Persistence/Doctrine/Migrations',
                ],
                'all_or_nothing' => true,
                'transactional' => true,
            ]),
            new ExistingEntityManager($c->get(EntityManagerInterface::class)),
        ),

        // Driving
        ResponseFactoryInterface::class => autowire(ResponseFactory::class),
        Session::class => autowire(PhpSession::class),
        Twig::class => static function (ContainerInterface $c): Twig {
            $twig = Twig::create(HttpApplication::TEMPLATES_DIR, [
                'debug' => $c->get('app.debug'),
                'strict_variables' => true,
            ]);
            $twig->getEnvironment()->addGlobal('session', $c->get(Session::class));

            return $twig;
        },
    ], $overrides);

    return $builder->build();
};
