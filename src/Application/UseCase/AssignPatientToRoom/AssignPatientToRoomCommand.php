<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\AssignPatientToRoom;

final readonly class AssignPatientToRoomCommand
{
    public function __construct(
        public string $ownerId,
        public string $roomId,
        public string $patientId,
    ) {
    }
}
