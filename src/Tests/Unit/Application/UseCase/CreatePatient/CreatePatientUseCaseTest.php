<?php

declare(strict_types=1);

namespace Acme\Tests\Unit\Application\UseCase\CreatePatient;

use Acme\Application\UseCase\CreatePatient\CreatePatientCommand;
use Acme\Application\UseCase\CreatePatient\CreatePatientUseCase;
use Acme\Domain\Patient\Exception\InvalidBirthDate;
use Acme\Domain\Patient\Exception\InvalidGender;
use Acme\Domain\Patient\Exception\InvalidPersonName;
use Acme\Domain\Patient\Gender;
use Acme\Tests\Unit\Double\InMemoryPatientRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CreatePatientUseCaseTest extends TestCase
{
    private InMemoryPatientRepository $patients;
    private CreatePatientUseCase $useCase;

    protected function setUp(): void
    {
        $this->patients = new InMemoryPatientRepository();
        $this->useCase = new CreatePatientUseCase($this->patients);
    }

    public function testItCreatesAPatientOwnedByTheAccount(): void
    {
        $response = $this->useCase->execute(new CreatePatientCommand('owner-id', ' Jane ', 'Doe', 'female', '1985-03-12'));

        $patient = $this->patients->findOneOwnedBy($response->patientId, 'owner-id');

        self::assertNotNull($patient);
        self::assertSame('Jane Doe', $patient->name()->fullName());
        self::assertSame(Gender::Female, $patient->gender());
        self::assertSame('1985-03-12', $patient->birthDate()->toString());
    }

    /**
     * @return iterable<string, array{CreatePatientCommand, class-string<\Throwable>}>
     */
    public static function invalidCommands(): iterable
    {
        yield 'missing first name' => [new CreatePatientCommand('owner-id', '', 'Doe', 'female', '1985-03-12'), InvalidPersonName::class];
        yield 'unknown gender' => [new CreatePatientCommand('owner-id', 'Jane', 'Doe', 'robot', '1985-03-12'), InvalidGender::class];
        yield 'malformed birth date' => [new CreatePatientCommand('owner-id', 'Jane', 'Doe', 'female', 'soon'), InvalidBirthDate::class];
    }

    /**
     * @param class-string<\Throwable> $exception
     */
    #[DataProvider('invalidCommands')]
    public function testItRejectsInvalidDetails(CreatePatientCommand $command, string $exception): void
    {
        try {
            $this->useCase->execute($command);
            self::fail('Invalid details should be rejected.');
        } catch (\Throwable $thrown) {
            self::assertInstanceOf($exception, $thrown);
        }

        self::assertSame([], $this->patients->findAllOwnedBy('owner-id'));
    }
}
