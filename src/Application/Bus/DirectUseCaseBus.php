<?php

declare(strict_types=1);

namespace Acme\Application\Bus;

use Closure;

final readonly class DirectUseCaseBus implements UseCaseBus
{
    /**
     * @param array<class-string, Closure(): object> $useCases Use case factories, indexed by the input class they handle
     */
    public function __construct(
        private array $useCases,
    ) {
    }

    public function execute(object $input): object
    {
        $useCase = ($this->useCases[$input::class] ?? throw new UnknownUseCase($input))();

        return $useCase->execute($input);
    }
}
