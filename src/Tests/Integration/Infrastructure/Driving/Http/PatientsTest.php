<?php

declare(strict_types=1);

namespace Acme\Tests\Integration\Infrastructure\Driving\Http;

use Acme\Domain\Patient\Gender;
use Acme\Domain\Patient\Patient;
use PHPUnit\Framework\Attributes\DataProvider;

final class PatientsTest extends HttpTestCase
{
    private const array JANE = ['first_name' => 'Jane', 'last_name' => 'Doe', 'gender' => 'female', 'birth_date' => '1985-03-12'];

    /**
     * @return iterable<string, array{string, string, array<string, string>}>
     */
    public static function patientRoutes(): iterable
    {
        $hacked = ['last_name' => 'Hacked'] + self::JANE;

        yield 'list' => ['GET', '/patients', []];
        yield 'new form' => ['GET', '/patients/new', []];
        yield 'create' => ['POST', '/patients', $hacked];
        yield 'edit form' => ['GET', '/patients/{id}/edit', []];
        yield 'update' => ['PUT', '/patients/{id}', $hacked];
        yield 'update from an HTML form' => ['POST', '/patients/{id}', ['_METHOD' => 'PUT'] + $hacked];
        yield 'delete' => ['DELETE', '/patients/{id}', []];
        yield 'delete from an HTML form' => ['POST', '/patients/{id}', ['_METHOD' => 'DELETE']];
    }

    /**
     * @param array<string, string> $form
     */
    #[DataProvider('patientRoutes')]
    public function testVisitorsAreSentToTheLoginPage(string $method, string $uri, array $form): void
    {
        $ownerId = $this->registerAndLogIn('john@acme.test');
        $patient = $this->givenPatientOf($ownerId);
        $this->request('POST', '/logout');
        $commits = $this->transactions->commits;
        $rollbacks = $this->transactions->rollbacks;

        $response = $this->request($method, str_replace('{id}', $patient->id(), $uri), $form);

        self::assertSame(303, $response->getStatusCode());
        self::assertSame('/login', $response->getHeaderLine('Location'));
        // The request was stopped before reaching the use case bus
        self::assertSame($commits, $this->transactions->commits);
        self::assertSame($rollbacks, $this->transactions->rollbacks);
        self::assertSame([$patient], $this->patients->findAllOwnedBy($ownerId));
        self::assertSame('Jane Doe', $patient->name()->fullName());
    }

    public function testANewAccountHasNoPatients(): void
    {
        $this->registerAndLogIn('john@acme.test');

        $response = $this->request('GET', '/patients');

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('You have not added any patients yet.', (string) $response->getBody());
    }

    public function testCreatingAPatient(): void
    {
        $accountId = $this->registerAndLogIn('john@acme.test');

        self::assertStringContainsString('<form method="post" action="/patients">', (string) $this->request('GET', '/patients/new')->getBody());

        $response = $this->request('POST', '/patients', self::JANE);

        self::assertSame(303, $response->getStatusCode());
        self::assertSame('/patients', $response->getHeaderLine('Location'));
        self::assertCount(1, $this->patients->findAllOwnedBy($accountId));

        $list = (string) $this->request('GET', '/patients')->getBody();
        self::assertStringContainsString('Doe, Jane', $list);
        self::assertStringContainsString('<time datetime="1985-03-12">12 Mar 1985</time>', $list);
    }

    public function testCreationErrorsAreShownOnTheForm(): void
    {
        $accountId = $this->registerAndLogIn('john@acme.test');
        $transactionsBefore = $this->transactions->rollbacks;

        $response = $this->request('POST', '/patients', ['first_name' => '<b>Jane</b>', 'birth_date' => '1985-02-30'] + self::JANE);

        self::assertSame(422, $response->getStatusCode());
        self::assertSame($transactionsBefore + 1, $this->transactions->rollbacks);
        self::assertStringContainsString('Please provide a valid birth date.', (string) $response->getBody());
        self::assertStringContainsString('value="&lt;b&gt;Jane&lt;/b&gt;"', (string) $response->getBody());
        self::assertSame([], $this->patients->findAllOwnedBy($accountId));
    }

    public function testEditingAPatient(): void
    {
        $patient = $this->givenPatientOf($this->registerAndLogIn('john@acme.test'));

        $response = $this->request('GET', "/patients/{$patient->id()}/edit");
        $body = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString("action=\"/patients/{$patient->id()}\"", $body);
        self::assertStringContainsString('<input type="hidden" name="_METHOD" value="PUT">', $body);
        self::assertStringContainsString('value="Jane"', $body);
        self::assertStringContainsString('<option value="female" selected>', $body);
    }

    public function testUpdatingAPatient(): void
    {
        $patient = $this->givenPatientOf($this->registerAndLogIn('john@acme.test'));

        $response = $this->request('POST', "/patients/{$patient->id()}", ['_METHOD' => 'PUT', 'last_name' => 'Smith', 'gender' => 'other'] + self::JANE);

        self::assertSame(303, $response->getStatusCode());
        self::assertSame('/patients', $response->getHeaderLine('Location'));
        self::assertSame('Jane Smith', $patient->name()->fullName());
        self::assertSame(Gender::Other, $patient->gender());
    }

    public function testUpdateErrorsAreShownOnTheForm(): void
    {
        $patient = $this->givenPatientOf($this->registerAndLogIn('john@acme.test'));

        $response = $this->request('POST', "/patients/{$patient->id()}", ['_METHOD' => 'PUT', 'last_name' => ' '] + self::JANE);

        self::assertSame(422, $response->getStatusCode());
        self::assertStringContainsString('Please provide a last name.', (string) $response->getBody());
        self::assertSame('Jane Doe', $patient->name()->fullName());
    }

    public function testDeletingAPatient(): void
    {
        $accountId = $this->registerAndLogIn('john@acme.test');
        $patient = $this->givenPatientOf($accountId);

        $response = $this->request('POST', "/patients/{$patient->id()}", ['_METHOD' => 'DELETE']);

        self::assertSame(303, $response->getStatusCode());
        self::assertSame('/patients', $response->getHeaderLine('Location'));
        self::assertSame([], $this->patients->findAllOwnedBy($accountId));
    }

    public function testAnotherAccountsPatientsAreOutOfReach(): void
    {
        $ownerId = $this->registerAndLogIn('john@acme.test');
        $patient = $this->givenPatientOf($ownerId);

        $this->registerAndLogIn('mallory@acme.test');

        self::assertStringNotContainsString('Doe, Jane', (string) $this->request('GET', '/patients')->getBody());
        self::assertSame(404, $this->request('GET', "/patients/{$patient->id()}/edit")->getStatusCode());
        self::assertSame(404, $this->request('POST', "/patients/{$patient->id()}", ['_METHOD' => 'PUT', 'last_name' => 'Hacked'] + self::JANE)->getStatusCode());
        self::assertSame(404, $this->request('POST', "/patients/{$patient->id()}", ['_METHOD' => 'DELETE'])->getStatusCode());
        self::assertSame('Jane Doe', $this->patients->findOneOwnedBy($patient->id(), $ownerId)?->name()->fullName());
    }

    private function givenPatientOf(string $accountId): Patient
    {
        $this->request('POST', '/patients', self::JANE);

        return $this->patients->findAllOwnedBy($accountId)[0];
    }
}
