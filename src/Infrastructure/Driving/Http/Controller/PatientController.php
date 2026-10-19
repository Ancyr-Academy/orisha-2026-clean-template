<?php

declare(strict_types=1);

namespace Acme\Infrastructure\Driving\Http\Controller;

use Acme\Application\Bus\UseCaseBus;
use Acme\Application\UseCase\CreatePatient\CreatePatientCommand;
use Acme\Application\UseCase\DeletePatient\DeletePatientCommand;
use Acme\Application\UseCase\GetPatient\GetPatientQuery;
use Acme\Application\UseCase\GetPatient\GetPatientResponse;
use Acme\Application\UseCase\ListPatients\ListPatientsQuery;
use Acme\Application\UseCase\ListPatients\ListPatientsResponse;
use Acme\Application\UseCase\UpdatePatient\UpdatePatientCommand;
use Acme\Domain\Patient\Exception\InvalidBirthDate;
use Acme\Domain\Patient\Exception\InvalidGender;
use Acme\Domain\Patient\Exception\InvalidPersonName;
use Acme\Domain\Patient\Exception\PatientNotFound;
use Acme\Domain\Patient\Gender;
use Acme\Domain\Patient\PersonName;
use Acme\Infrastructure\Driving\Http\Middleware\RequireAuthentication;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Views\Twig;

/**
 * @phpstan-type PatientForm array{first_name: string, last_name: string, gender: string, birth_date: string}
 */
final readonly class PatientController
{
    private const array FIELDS = ['first_name', 'last_name', 'gender', 'birth_date'];

    public function __construct(
        private UseCaseBus $useCases,
        private Twig $twig,
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        /** @var ListPatientsResponse $result */
        $result = $this->useCases->execute(new ListPatientsQuery(self::accountId($request)));

        return $this->twig
            ->render($response, 'patients.html.twig', ['patients' => $result->patients])
            ->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    public function new(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->renderForm($response, array_fill_keys(self::FIELDS, ''), patientId: null, error: null);
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $form = self::submittedForm($request);

        try {
            $this->useCases->execute(new CreatePatientCommand(
                self::accountId($request),
                $form['first_name'],
                $form['last_name'],
                $form['gender'],
                $form['birth_date'],
            ));
        } catch (InvalidPersonName | InvalidGender | InvalidBirthDate $exception) {
            return $this->renderForm($response->withStatus(422), $form, null, $exception->getMessage());
        }

        return $response->withStatus(303)->withHeader('Location', '/patients');
    }

    /**
     * @param array{id: string} $args
     */
    public function edit(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            /** @var GetPatientResponse $patient */
            $patient = $this->useCases->execute(new GetPatientQuery(self::accountId($request), $args['id']));
        } catch (PatientNotFound $exception) {
            throw new HttpNotFoundException($request, previous: $exception);
        }

        return $this->renderForm($response, [
            'first_name' => $patient->firstName,
            'last_name' => $patient->lastName,
            'gender' => $patient->gender,
            'birth_date' => $patient->birthDate,
        ], $patient->id, null);
    }

    /**
     * @param array{id: string} $args
     */
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $form = self::submittedForm($request);

        try {
            $this->useCases->execute(new UpdatePatientCommand(
                self::accountId($request),
                $args['id'],
                $form['first_name'],
                $form['last_name'],
                $form['gender'],
                $form['birth_date'],
            ));
        } catch (PatientNotFound $exception) {
            throw new HttpNotFoundException($request, previous: $exception);
        } catch (InvalidPersonName | InvalidGender | InvalidBirthDate $exception) {
            return $this->renderForm($response->withStatus(422), $form, $args['id'], $exception->getMessage());
        }

        return $response->withStatus(303)->withHeader('Location', '/patients');
    }

    /**
     * @param array{id: string} $args
     */
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $this->useCases->execute(new DeletePatientCommand(self::accountId($request), $args['id']));
        } catch (PatientNotFound $exception) {
            throw new HttpNotFoundException($request, previous: $exception);
        }

        return $response->withStatus(303)->withHeader('Location', '/patients');
    }

    /**
     * @param PatientForm $form
     */
    private function renderForm(ResponseInterface $response, array $form, ?string $patientId, ?string $error): ResponseInterface
    {
        return $this->twig
            ->render($response, 'patient_form.html.twig', [
                'form' => $form,
                'patient_id' => $patientId,
                'error' => $error,
                'genders' => array_map(static fn (Gender $gender): string => $gender->value, Gender::cases()),
                'maximum_name_length' => PersonName::MAXIMUM_LENGTH,
                'today' => date('Y-m-d'),
            ])
            ->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * @return PatientForm
     */
    private static function submittedForm(ServerRequestInterface $request): array
    {
        $data = (array) $request->getParsedBody();

        /** @var PatientForm */
        return array_combine(self::FIELDS, array_map(
            static fn (string $field): string => is_string($data[$field] ?? null) ? $data[$field] : '',
            self::FIELDS,
        ));
    }

    private static function accountId(ServerRequestInterface $request): string
    {
        return $request->getAttribute(RequireAuthentication::ACCOUNT_ID);
    }
}
