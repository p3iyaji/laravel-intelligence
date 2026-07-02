<?php

namespace App\Services;

use App\Models\User;

class UserRepository
{
    public function all(): array
    {
        return User::all()->toArray();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }
}
