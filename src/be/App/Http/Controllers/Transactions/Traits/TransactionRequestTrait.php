<?php
declare(strict_types=1);

namespace App\Http\Controllers\Transactions\Traits;

use App\Http\Controllers\Transactions\Enums\TransactionTypes;
use Illuminate\Validation\Rule;

trait TransactionRequestTrait
{
    /**
     * Get common validation rules
     *
     * @return array<string, mixed>
     */
    protected function getCommonRules(): array
    {
        return [
            'name' => ['string', 'min:3', 'max:255'],
            'type' => [Rule::in(TransactionTypes::values())],
        ];
    }

    /**
     * Get common validation messages
     *
     * @return array<string, string>
     */
    protected function getCommonMessages(): array
    {
        return [
            'name.required' => 'Şirkət adını qeyd edərdiniz.',
            'name.min' => 'Şirkət adını minimum 3 simvol qeyd edərdiniz.',
            'type.required' => 'Müştəri tipini qeyd edərdiniz.',
            'type.in' => 'Müştəri tipini ancaq Fərdi, Biznes  qeyd edərdiniz.'
        ];
    }

    /**
     * Frontend'dən gələn camelCase formatını snake_case formatına çeviririk
     */
    protected function prepareForValidation(): void
    {
        $data = [];
        if (isset($this->name)) $data['name'] = $this->name;
        if (isset($this->type)) $data['type'] = $this->type;
        $this->merge($data);
    }
}