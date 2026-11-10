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

    protected function exportHeadings(): array
    {
        return ['name', 'short_name'];
    }

    protected function exportRow(Model $record): array
    {
        return [$record->name, $record->short_name];
    }

    protected function templateRows(): array
    {
        return [['Cup', 'cup'], ['Pcs', 'pcs']];
    }

    protected function importRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'short_name' => ['required', 'string', 'max:10'],
        ];
    }

    protected function importRow(array $row): void
    {
        $shortName = trim((string) $row['short_name']);
        Unit::query()->updateOrCreate(
            ['short_name' => $shortName],
            ['name' => trim((string) $row['name'])],
        );
    }

    protected function rules(?Model $record = null): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'short_name' => ['required', 'string', 'max:10'],
        ];
    }
}
