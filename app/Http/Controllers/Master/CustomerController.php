<?php

namespace App\Http\Controllers\Master;

use App\Models\Customer;
use App\Models\CustomerGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends SimpleCrudController
{
    protected string $model = Customer::class;

    protected string $title = 'Pelanggan';

    protected string $singular = 'Pelanggan';

    protected string $routePrefix = 'master.customers';

    protected string $icon = 'bi bi-people';

    protected array $searchable = ['name', 'phone', 'email'];

    protected array $columns = [
        'name' => 'Nama',
        'phone' => 'No. HP',
        'email' => 'Email',
        'loyalty_points' => 'Poin',
    ];

    public function index(Request $request)
    {
        $this->fields = [
            ['name' => 'name', 'label' => 'Nama Pelanggan', 'type' => 'text', 'required' => true],
            ['name' => 'phone', 'label' => 'No. HP', 'type' => 'text', 'required' => true],
            ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
            [
                'name' => 'customer_group_id', 'label' => 'Grup Pelanggan', 'type' => 'select',
                'options' => CustomerGroup::query()->orderBy('name')->pluck('name', 'id')->all(),
            ],
            ['name' => 'loyalty_points', 'label' => 'Poin Loyalitas', 'type' => 'number', 'default' => 0],
        ];

        return parent::index($request)->with('groups', CustomerGroup::query()->orderBy('name')->get());
    }

    protected function rules(?Model $record = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20', Rule::unique('customers', 'phone')->ignore($record?->id)],
            'email' => ['nullable', 'email', 'max:100'],
            'customer_group_id' => ['nullable', 'exists:customer_groups,id'],
            'loyalty_points' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function beforeSave(array &$data, ?Model $record = null): void
    {
        $data['loyalty_points'] = (int) ($data['loyalty_points'] ?? 0);
    }
}
