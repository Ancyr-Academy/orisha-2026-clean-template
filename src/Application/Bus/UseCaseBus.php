<?php

declare(strict_types=1);

namespace Acme\Application\Bus;

/**
 * Single entry point to the use cases, so that cross-cutting concerns (transactions...) apply to all of them.
 */
interface UseCaseBus
{
    /**
     * Executes the use case handling the given input (command or query) and returns its response.
     *
     * @throws UnknownUseCase when no use case handles the input
     */
    public function execute(object $input): object;
}
