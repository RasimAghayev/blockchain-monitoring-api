<?php

declare(strict_types=1);

namespace App\Filters;

use Illuminate\Http\Request;

class ApiFilter
{
    protected array $safeParms = [];

    protected array $columnMap = [];

    protected array $operatorMap = [
        'eq' => '=',
        'lt' => '<',
        'lte' => '<=',
        'gt' => '>',
        'gte' => '>=',
        'ne' => '!=',
        'lk' => 'LIKE',
        'ilk' => 'ILIKE',
        'nlk' => 'NOT LIKE',
        'inlk' => 'NOT ILIKE',
        'bt' => 'BETWEEN',
        'nbt' => 'NOT BETWEEN',
        'in' => 'IN',
        'nin' => 'NOT IN',
        'json' => 'JSON_CONTAINS',
    ];

    public function transform(Request $request): array
    {
        $eloQuery = [];

        foreach ($this->safeParms as $parm => $operators) {
            $query = $request->query($parm);
            if (! $query) {
                continue;
            }

            $column = $this->columnMap[$parm] ?? $parm;

            foreach ($operators as $operator) {
                if (isset($query[$operator])) {
                    if ($result = $this->buildQuery($column, $operator, $query[$operator])) {
                        $eloQuery[] = $result;
                    }
                }
            }
        }

        return $eloQuery;
    }

    protected function buildQuery($column, $operator, $value): ?array
    {
        $mappedOperator = $this->operatorMap[$operator] ?? null;
        if (! $mappedOperator) {
            return null;
        }

        return match ($operator) {
            'lk', 'nlk', 'ilk', 'inlk' => [$column, $mappedOperator, '%'.$value.'%'],
            'bt', 'nbt' => is_array($value) && count($value) === 2
                ? [$column, $mappedOperator, $value]
                : null,
            'in', 'nin' => is_array($value)
                ? [$column, $mappedOperator, $value]
                : null,
            // 'json' used to be special-cased here as [$mappedOperator, $column, $value]
            // — a reversed tuple vs. every other branch (docs/REPOSITORY_AUDIT.md P3).
            // The correct shape, [$column, $mappedOperator, $value], is exactly what the
            // default arm below already produces, so the dedicated branch was both buggy
            // and redundant; removing it fixes the bug and the duplication in one move.
            default => [$column, $mappedOperator, $value]
        };
    }
}
