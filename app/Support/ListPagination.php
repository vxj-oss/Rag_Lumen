<?php

namespace App\Support;

use Illuminate\Pagination\LengthAwarePaginator;

class ListPagination
{
    public static function size(): int
    {
        return request()->integer('per_page') === 2 ? 2 : 5;
    }

    public static function collection($items, string $name = 'page'): LengthAwarePaginator
    {
        $page = max(1, request()->integer($name, 1));
        $size = self::size();

        return (new LengthAwarePaginator($items->values()->forPage($page, $size)->values(), $items->count(), $size, $page, ['path' => request()->url(), 'pageName' => $name]))->withQueryString();
    }
}
