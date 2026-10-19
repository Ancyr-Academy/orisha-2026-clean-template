<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\DeclareRoom;

use Acme\Application\Ports\RoomRepository;
use Acme\Domain\Room\Exception\InvalidRoomCapacity;
use Acme\Domain\Room\Exception\InvalidRoomName;
use Acme\Domain\Room\Exception\RoomNameAlreadyInUse;
use Acme\Domain\Room\Room;

final readonly class DeclareRoomUseCase
{
    public function __construct(
        private RoomRepository $rooms,
    ) {
    }

    /**
     * @throws InvalidRoomName
     * @throws InvalidRoomCapacity
     * @throws RoomNameAlreadyInUse
     */
    public function execute(DeclareRoomCommand $command): DeclareRoomResponse
    {
        $room = Room::declare($this->rooms->nextIdentity(), $command->ownerId, $command->name, $command->capacity);

        // Room names only need to be unique within the account
        if ($this->rooms->findOneByName($room->name(), $command->ownerId) !== null) {
            throw new RoomNameAlreadyInUse($room->name());
        }

        $this->rooms->save($room);

        return new DeclareRoomResponse($room->id());
    }
}
