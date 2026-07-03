<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class TypedModel extends Model
{
    public function findById(int|string $id): self|null
    {
        return $this->newQuery()->find($id);
    }

    public function getTags(): array|Collection
    {
        return [];
    }

    public function resolve(): Model&\JsonSerializable
    {
        return $this;
    }
}
