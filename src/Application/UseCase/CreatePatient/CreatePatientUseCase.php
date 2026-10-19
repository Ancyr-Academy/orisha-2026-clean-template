<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\CreatePatient;

use Acme\Application\Ports\PatientRepository;
use Acme\Domain\Patient\BirthDate;
use Acme\Domain\Patient\Exception\InvalidBirthDate;
use Acme\Domain\Patient\Exception\InvalidGender;
use Acme\Domain\Patient\Exception\InvalidPersonName;
use Acme\Domain\Patient\Gender;
use Acme\Domain\Patient\Patient;
use Acme\Domain\Patient\PersonName;

final readonly class CreatePatientUseCase
{
    public function __construct(
        private PatientRepository $patients,
    ) {
    }

    /**
     * @throws InvalidPersonName
     * @throws InvalidGender
     * @throws InvalidBirthDate
     */
    public function execute(CreatePatientCommand $command): CreatePatientResponse
    {
        $patient = Patient::register(
            $this->patients->nextIdentity(),
            $command->ownerId,
            PersonName::of($command->firstName, $command->lastName),
            Gender::fromString($command->gender),
            BirthDate::fromString($command->birthDate),
        );

        $this->patients->save($patient);

        return new CreatePatientResponse($patient->id());
    }
}
