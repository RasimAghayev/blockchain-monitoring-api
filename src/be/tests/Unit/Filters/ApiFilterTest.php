<?php

declare(strict_types=1);

namespace Tests\Unit\Filters;

use App\Filters\ApiFilter;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * ApiFilter::buildQuery() used to special-case the 'json' operator as
 * [$mappedOperator, $column, $value] — every other operator produces
 * [$column, $mappedOperator, $value], a reversed tuple that would have been
 * fed straight into Eloquent's where() as a malformed condition
 * (docs/REPOSITORY_AUDIT.md P3). No concrete filter (TokenFilters,
 * TransactionFilters) allow-lists a column for 'json' today — there is no
 * JSON column in this schema — so this only exercises ApiFilter itself via a
 * throwaway subclass, not a real endpoint.
 */
class ApiFilterTest extends TestCase
{
    public function test_json_operator_produces_the_same_tuple_shape_as_every_other_operator(): void
    {
        $filter = new class extends ApiFilter
        {
            protected array $safeParms = [
                'data' => ['json', 'eq'],
            ];

            protected array $columnMap = [
                'data' => 'metadata',
            ];
        };

        $request = Request::create('/', 'GET', [
            'data' => ['json' => 'active'],
        ]);

        $result = $filter->transform($request);

        $this->assertSame([['metadata', 'JSON_CONTAINS', 'active']], $result);
    }

    public function test_an_unmapped_operator_is_silently_dropped(): void
    {
        $filter = new class extends ApiFilter
        {
            protected array $safeParms = [
                'data' => ['bogus'],
            ];

            protected array $columnMap = [
                'data' => 'metadata',
            ];
        };

        $request = Request::create('/', 'GET', [
            'data' => ['bogus' => 'x'],
        ]);

        $this->assertSame([], $filter->transform($request));
    }
}
