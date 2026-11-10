<?php

namespace App\Http\Controllers\Master;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;

class UnitController extends SimpleCrudController
{
    protected string $model = Unit::class;

    protected string $title = 'Satuan Produk';

    protected string $singular = 'Satuan';

    protected string $routePrefix = 'master.units';

    protected string $icon = 'bi bi-rulers';

    protected array $columns = ['name' => 'Nama Satuan', 'short_name' => 'Singkatan'];

    protected array $fields = [
        ['name' => 'name', 'label' => 'Nama Satuan', 'type' => 'text', 'required' => true],
        ['name' => 'short_name', 'label' => 'Singkatan', 'type' => 'text', 'required' => true],
    ];

    protected function rules(?Model $record = null): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'short_name' => ['required', 'string', 'max:10'],
        ];
    }
}
