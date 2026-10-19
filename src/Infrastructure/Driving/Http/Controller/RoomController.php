<?php

declare(strict_types=1);

namespace Acme\Infrastructure\Driving\Http\Controller;

use Acme\Application\Bus\UseCaseBus;
use Acme\Application\UseCase\AssignPatientToRoom\AssignPatientToRoomCommand;
use Acme\Application\UseCase\DeclareRoom\DeclareRoomCommand;
use Acme\Application\UseCase\ListRooms\ListRoomsQuery;
use Acme\Application\UseCase\ListRooms\ListRoomsResponse;
use Acme\Application\UseCase\UnassignPatientFromRoom\UnassignPatientFromRoomCommand;
use Acme\Domain\Patient\Exception\PatientNotFound;
use Acme\Domain\Room\Exception\InvalidRoomCapacity;
use Acme\Domain\Room\Exception\InvalidRoomName;
use Acme\Domain\Room\Exception\PatientAlreadyAssigned;
use Acme\Domain\Room\Exception\PatientNotAssigned;
use Acme\Domain\Room\Exception\RoomIsFull;
use Acme\Domain\Room\Exception\RoomNameAlreadyInUse;
use Acme\Domain\Room\Exception\RoomNotFound;
use Acme\Domain\Room\Room;
use Acme\Infrastructure\Driving\Http\Middleware\RequireAuthentication;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Views\Twig;

final readonly class RoomController
{
    public function __construct(
        private UseCaseBus $useCases,
        private Twig $twig,
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->renderRooms($request, $response, error: null);
    }

    public function new(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->renderForm($response, name: '', capacity: '', error: null);
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $name = is_string($data['name'] ?? null) ? $data['name'] : '';
        $capacity = is_string($data['capacity'] ?? null) ? $data['capacity'] : '';

        try {
            // A capacity that is not a whole number is reported by the domain like any capacity below one
            $this->useCases->execute(new DeclareRoomCommand(
                self::accountId($request),
                $name,
                (int) filter_var($capacity, FILTER_VALIDATE_INT),
            ));
        } catch (InvalidRoomName | InvalidRoomCapacity | RoomNameAlreadyInUse $exception) {
            return $this->renderForm($response->withStatus(422), $name, $capacity, $exception->getMessage());
        }

        return $response->withStatus(303)->withHeader('Location', '/rooms');
    }

    /**
     * @param array{id: string} $args
     */
    public function assign(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $patientId = is_string($data['patient_id'] ?? null) ? $data['patient_id'] : '';

        try {
            $this->useCases->execute(new AssignPatientToRoomCommand(self::accountId($request), $args['id'], $patientId));
        } catch (PatientNotFound | RoomNotFound $exception) {
            throw new HttpNotFoundException($request, previous: $exception);
        } catch (RoomIsFull | PatientAlreadyAssigned $exception) {
            return $this->renderRooms($request, $response->withStatus(409), $exception->getMessage());
        }

        return $response->withStatus(303)->withHeader('Location', '/rooms');
    }

    /**
     * @param array{id: string, patientId: string} $args
     */
    public function unassign(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $this->useCases->execute(new UnassignPatientFromRoomCommand(self::accountId($request), $args['id'], $args['patientId']));
        } catch (PatientNotFound | RoomNotFound $exception) {
            throw new HttpNotFoundException($request, previous: $exception);
        } catch (PatientNotAssigned $exception) {
            return $this->renderRooms($request, $response->withStatus(409), $exception->getMessage());
        }

        return $response->withStatus(303)->withHeader('Location', '/rooms');
    }

    private function renderRooms(ServerRequestInterface $request, ResponseInterface $response, ?string $error): ResponseInterface
    {
        /** @var ListRoomsResponse $result */
        $result = $this->useCases->execute(new ListRoomsQuery(self::accountId($request)));

        return $this->twig
            ->render($response, 'rooms.html.twig', [
                'rooms' => $result->rooms,
                'unassigned_patients' => $result->unassignedPatients,
                'error' => $error,
            ])
            ->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    private function renderForm(ResponseInterface $response, string $name, string $capacity, ?string $error): ResponseInterface
    {
        return $this->twig
            ->render($response, 'room_form.html.twig', [
                'name' => $name,
                'capacity' => $capacity,
                'error' => $error,
                'maximum_name_length' => Room::MAXIMUM_NAME_LENGTH,
            ])
            ->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    private static function accountId(ServerRequestInterface $request): string
    {
        return $request->getAttribute(RequireAuthentication::ACCOUNT_ID);
    }
}
