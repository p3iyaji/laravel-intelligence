<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class CostlyService
{
    public function run(): array
    {
        $rows = DB::raw('select * from users');
        $remote = Http::get('https://example.com/api/users');
        $matrix = [];

        foreach ([1, 2, 3] as $left) {
            foreach ([4, 5, 6] as $right) {
                $matrix[] = $left + $right;
            }
        }

        return [$rows, $remote->json(), $matrix];
    }
}
