<?php

namespace App\Support;

use Illuminate\Pagination\LengthAwarePaginator;

class ListPagination
{
    public static function size(): int
    {
        if (request()->has('per_page')) {
            return request()->integer('per_page') === 2 ? 2 : 5;
        }
        $preference = request()->cookie('workspace_list_size');
        if (in_array((string) $preference, ['2', '5'], true)) {
            return (int) $preference;
        }
        return request()->header('Sec-CH-UA-Mobile') === '?1'
            || preg_match('/Mobile|Android|iPhone|iPad|iPod|IEMobile/i', request()->userAgent() ?? '') ? 2 : 5;
    }

    public static function collection($items, string $name = 'page'): LengthAwarePaginator
    {
        $page = max(1, request()->integer($name, 1));
        $size = self::size();

        return (new LengthAwarePaginator($items->values()->forPage($page, $size)->values(), $items->count(), $size, $page, ['path' => request()->url(), 'pageName' => $name]))->withQueryString();
    }
}
