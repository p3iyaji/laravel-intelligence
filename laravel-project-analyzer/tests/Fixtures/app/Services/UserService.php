<?php

namespace App\Services;

class UserService
{
    public function __construct(
        private readonly UserRepository $repository
    ) {}

    public function findAll(): array
    {
        return $this->repository->all();
    }

    public function create(array $data): mixed
    {
        return $this->repository->create($data);
    }
}
