<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\UpdatePatient;

use Acme\Application\Ports\PatientRepository;
use Acme\Domain\Patient\BirthDate;
use Acme\Domain\Patient\Exception\InvalidBirthDate;
use Acme\Domain\Patient\Exception\InvalidGender;
use Acme\Domain\Patient\Exception\InvalidPersonName;
use Acme\Domain\Patient\Exception\PatientNotFound;
use Acme\Domain\Patient\Gender;
use Acme\Domain\Patient\PersonName;

final readonly class UpdatePatientUseCase
{
    public function __construct(
        private PatientRepository $patients,
    ) {
    }

    /**
     * @throws PatientNotFound
     * @throws InvalidPersonName
     * @throws InvalidGender
     * @throws InvalidBirthDate
     */
    public function execute(UpdatePatientCommand $command): UpdatePatientResponse
    {
        $patient = $this->patients->findOneOwnedBy($command->patientId, $command->ownerId)
            ?? throw new PatientNotFound($command->patientId);

        $patient->updateDetails(
            PersonName::of($command->firstName, $command->lastName),
            Gender::fromString($command->gender),
            BirthDate::fromString($command->birthDate),
        );

        $this->patients->save($patient);

        return new UpdatePatientResponse($patient->id());
    }
}
