<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\UnassignPatientFromRoom;

final readonly class UnassignPatientFromRoomResponse
{
    public function __construct(
        public string $roomId,
        public string $patientId,
    ) {
    }
}
