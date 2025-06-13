<?php

declare(strict_types=1);

namespace App\Context\User\Domain;

interface UserRepository
{
    public function save(User $user, bool $flush): void;
    public function findOneByIdOrFail(string $id): User;

}