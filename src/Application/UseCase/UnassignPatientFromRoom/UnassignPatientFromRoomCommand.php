<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\UnassignPatientFromRoom;

final readonly class UnassignPatientFromRoomCommand
{
    public function __construct(
        public string $ownerId,
        public string $roomId,
        public string $patientId,
    ) {
    }
}
