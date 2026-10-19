<?php

declare(strict_types=1);

namespace Acme\Tests\Integration\Infrastructure\Driving\Http;

use Acme\Domain\Patient\Patient;
use Acme\Domain\Room\Room;
use PHPUnit\Framework\Attributes\DataProvider;

final class RoomsTest extends HttpTestCase
{
    /**
     * @return iterable<string, array{string, string, array<string, string>}>
     */
    public static function roomRoutes(): iterable
    {
        yield 'list' => ['GET', '/rooms', []];
        yield 'new form' => ['GET', '/rooms/new', []];
        yield 'declare' => ['POST', '/rooms', ['name' => '999', 'capacity' => '1']];
        yield 'assign' => ['POST', '/rooms/{room}/occupants', ['patient_id' => '{patient}']];
        yield 'unassign' => ['POST', '/rooms/{room}/occupants/{patient}', ['_METHOD' => 'DELETE']];
    }

    /**
     * @param array<string, string> $form
     */
    #[DataProvider('roomRoutes')]
    public function testVisitorsAreSentToTheLoginPage(string $method, string $uri, array $form): void
    {
        $accountId = $this->registerAndLogIn('john@acme.test');
        $patient = $this->givenPatient('Jane');
        $room = $this->givenRoom('101', 2);
        $this->request('POST', '/logout');
        $commits = $this->transactions->commits;
        $placeholders = ['{room}' => $room->id(), '{patient}' => $patient->id()];

        $response = $this->request($method, strtr($uri, $placeholders), array_map(static fn (string $value): string => strtr($value, $placeholders), $form));

        self::assertSame(303, $response->getStatusCode());
        self::assertSame('/login', $response->getHeaderLine('Location'));
        self::assertSame($commits, $this->transactions->commits);
        self::assertSame([$room], $this->rooms->findAllOwnedBy($accountId));
        self::assertSame([], $room->occupantIds());
    }

    public function testDeclaringARoom(): void
    {
        $accountId = $this->registerAndLogIn('john@acme.test');

        $response = $this->request('POST', '/rooms', ['name' => '101', 'capacity' => '2']);

        self::assertSame(303, $response->getStatusCode());
        self::assertSame('/rooms', $response->getHeaderLine('Location'));
        self::assertSame(2, $this->rooms->findOneByName('101', $accountId)?->capacity());
        self::assertStringContainsString('0 / 2 beds taken', (string) $this->request('GET', '/rooms')->getBody());
    }

    public function testDeclarationErrorsAreShownOnTheForm(): void
    {
        $accountId = $this->registerAndLogIn('john@acme.test');

        $response = $this->request('POST', '/rooms', ['name' => '<b>101</b>', 'capacity' => 'two']);

        self::assertSame(422, $response->getStatusCode());
        self::assertStringContainsString('A room must have at least one bed.', (string) $response->getBody());
        self::assertStringContainsString('value="&lt;b&gt;101&lt;/b&gt;"', (string) $response->getBody());
        self::assertSame([], $this->rooms->findAllOwnedBy($accountId));
    }

    public function testAssigningAndUnassigningAPatient(): void
    {
        $this->registerAndLogIn('john@acme.test');
        $patient = $this->givenPatient('Jane');
        $room = $this->givenRoom('101', 1);

        $response = $this->request('POST', "/rooms/{$room->id()}/occupants", ['patient_id' => $patient->id()]);

        self::assertSame(303, $response->getStatusCode());
        self::assertSame([$patient->id()], $room->occupantIds());
        $page = (string) $this->request('GET', '/rooms')->getBody();
        self::assertStringContainsString('1 / 1 bed taken', $page);
        self::assertStringContainsString('Jane Doe', $page);
        self::assertStringContainsString('This room is full.', $page);

        $response = $this->request('POST', "/rooms/{$room->id()}/occupants/{$patient->id()}", ['_METHOD' => 'DELETE']);

        self::assertSame(303, $response->getStatusCode());
        self::assertSame([], $room->occupantIds());
    }

    public function testAFullRoomRejectsAnotherPatient(): void
    {
        $this->registerAndLogIn('john@acme.test');
        $jane = $this->givenPatient('Jane');
        $john = $this->givenPatient('John');
        $room = $this->givenRoom('101', 1);
        $this->request('POST', "/rooms/{$room->id()}/occupants", ['patient_id' => $jane->id()]);

        $response = $this->request('POST', "/rooms/{$room->id()}/occupants", ['patient_id' => $john->id()]);

        self::assertSame(409, $response->getStatusCode());
        self::assertStringContainsString('Room 101 is full.', (string) $response->getBody());
        self::assertSame([$jane->id()], $room->occupantIds());
    }

    public function testAnotherAccountsRoomsAreOutOfReach(): void
    {
        $this->registerAndLogIn('john@acme.test');
        $jane = $this->givenPatient('Jane');
        $room = $this->givenRoom('101', 2);
        $this->request('POST', "/rooms/{$room->id()}/occupants", ['patient_id' => $jane->id()]);

        $this->registerAndLogIn('mallory@acme.test');
        $zoe = $this->givenPatient('Zoe');

        self::assertStringContainsString('You have not declared any rooms yet.', (string) $this->request('GET', '/rooms')->getBody());
        self::assertSame(404, $this->request('POST', "/rooms/{$room->id()}/occupants", ['patient_id' => $zoe->id()])->getStatusCode());
        self::assertSame(404, $this->request('POST', "/rooms/{$room->id()}/occupants/{$jane->id()}", ['_METHOD' => 'DELETE'])->getStatusCode());
        self::assertSame([$jane->id()], $room->occupantIds());
    }

    public function testAnotherAccountsPatientCannotBeAssigned(): void
    {
        $this->registerAndLogIn('john@acme.test');
        $jane = $this->givenPatient('Jane');

        $this->registerAndLogIn('mallory@acme.test');
        $room = $this->givenRoom('101', 2);
        $response = $this->request('POST', "/rooms/{$room->id()}/occupants", ['patient_id' => $jane->id()]);

        self::assertSame(404, $response->getStatusCode());
        self::assertSame([], $room->occupantIds());
    }

    public function testTwoAccountsMayEachHaveARoomWithTheSameName(): void
    {
        $this->registerAndLogIn('john@acme.test');
        $this->givenRoom('101', 2);

        $this->registerAndLogIn('mallory@acme.test');
        $response = $this->request('POST', '/rooms', ['name' => '101', 'capacity' => '1']);

        self::assertSame(303, $response->getStatusCode());
    }

    private function givenRoom(string $name, int $capacity): Room
    {
        $this->request('POST', '/rooms', ['name' => $name, 'capacity' => (string) $capacity]);

        return $this->rooms->findOneByName($name, $this->session->get('account')['id'])
            ?? self::fail("Room $name was not declared.");
    }

    /**
     * Creates a patient for the logged-in account.
     */
    private function givenPatient(string $firstName): Patient
    {
        $this->request('POST', '/patients', ['first_name' => $firstName, 'last_name' => 'Doe', 'gender' => 'unknown', 'birth_date' => '1985-03-12']);

        $patients = $this->patients->findAllOwnedBy($this->session->get('account')['id']);

        foreach ($patients as $patient) {
            if ($patient->name()->firstName === $firstName) {
                return $patient;
            }
        }

        self::fail("Patient $firstName was not created.");
    }
}
