<?php

declare(strict_types=1);

namespace Acme\Infrastructure\Driving\Http\Controller;

use Acme\Application\Bus\UseCaseBus;
use Acme\Application\UseCase\RegisterAccount\PasswordTooShort;
use Acme\Application\UseCase\RegisterAccount\RegisterAccountCommand;
use Acme\Application\UseCase\RegisterAccount\RegisterAccountResponse;
use Acme\Application\UseCase\RegisterAccount\RegisterAccountUseCase;
use Acme\Domain\Account\Exception\EmailAlreadyInUse;
use Acme\Domain\Account\Exception\InvalidEmail;
use Acme\Infrastructure\Driving\Http\Session\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;

final readonly class RegisterController
{
    public function __construct(
        private UseCaseBus $useCases,
        private Session $session,
        private Twig $twig,
    ) {
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->render($response, email: '', error: null);
    }

    public function submit(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = (array) $request->getParsedBody();
        $email = is_string($data['email'] ?? null) ? $data['email'] : '';
        $password = is_string($data['password'] ?? null) ? $data['password'] : '';

        try {
            /** @var RegisterAccountResponse $result */
            $result = $this->useCases->execute(new RegisterAccountCommand($email, $password));
        } catch (InvalidEmail | PasswordTooShort | EmailAlreadyInUse $exception) {
            return $this->render($response->withStatus(422), $email, $exception->getMessage());
        }

        $this->session->regenerate();
        $this->session->set('account', ['id' => $result->accountId, 'email' => $result->email]);

        return $response->withStatus(303)->withHeader('Location', '/');
    }

    private function render(ResponseInterface $response, string $email, ?string $error): ResponseInterface
    {
        return $this->twig
            ->render($response, 'register.html.twig', [
                'email' => $email,
                'error' => $error,
                'minimum_password_length' => RegisterAccountUseCase::MINIMUM_PASSWORD_LENGTH,
            ])
            ->withHeader('Content-Type', 'text/html; charset=utf-8');
    }
}
