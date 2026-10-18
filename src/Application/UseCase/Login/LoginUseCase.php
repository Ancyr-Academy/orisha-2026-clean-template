<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\Login;

use Acme\Domain\Account\AccountRepository;
use Acme\Domain\Account\Email;
use Acme\Domain\Account\Exception\InvalidEmail;
use Acme\Domain\Account\PasswordHasher;

final readonly class LoginUseCase
{
    public function __construct(
        private AccountRepository $accounts,
        private PasswordHasher $passwordHasher,
    ) {
    }

    /**
     * @throws InvalidCredentials
     */
    public function execute(LoginCommand $command): LoginResponse
    {
        try {
            $email = Email::fromString($command->email);
        } catch (InvalidEmail) {
            throw new InvalidCredentials();
        }

        $account = $this->accounts->findByEmail($email);

        if ($account === null || !$this->passwordHasher->verify($command->password, $account->passwordHash())) {
            throw new InvalidCredentials();
        }

        return new LoginResponse($account->id(), $account->email()->value);
    }
}
