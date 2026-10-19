<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\AssignPatientToRoom;

final readonly class AssignPatientToRoomResponse
{
    public function __construct(
        public string $roomId,
        public string $patientId,
    ) {
    }
}
