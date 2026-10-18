<?php

declare(strict_types=1);

namespace Acme\Application\UseCase\RegisterAccount;

use Acme\Domain\Account\Account;
use Acme\Domain\Account\AccountRepository;
use Acme\Domain\Account\Email;
use Acme\Domain\Account\Exception\EmailAlreadyInUse;
use Acme\Domain\Account\Exception\InvalidEmail;
use Acme\Domain\Account\PasswordHasher;
use DateTimeImmutable;

final readonly class RegisterAccountUseCase
{
    public const int MINIMUM_PASSWORD_LENGTH = 8;

    public function __construct(
        private AccountRepository $accounts,
        private PasswordHasher $passwordHasher,
    ) {
    }

    /**
     * @throws InvalidEmail
     * @throws PasswordTooShort
     * @throws EmailAlreadyInUse
     */
    public function execute(RegisterAccountCommand $command): RegisterAccountResponse
    {
        $email = Email::fromString($command->email);

        if (mb_strlen($command->password) < self::MINIMUM_PASSWORD_LENGTH) {
            throw new PasswordTooShort(self::MINIMUM_PASSWORD_LENGTH);
        }

        if ($this->accounts->findByEmail($email) !== null) {
            throw new EmailAlreadyInUse($email);
        }

        $account = Account::register(
            $this->accounts->nextIdentity(),
            $email,
            $this->passwordHasher->hash($command->password),
            new DateTimeImmutable(),
        );

        $this->accounts->save($account);

        return new RegisterAccountResponse($account->id(), $account->email()->value);
    }
}
