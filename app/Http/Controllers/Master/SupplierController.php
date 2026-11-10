<?php

namespace App\Http\Controllers\Master;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class SupplierController extends SimpleCrudController
{
    protected string $model = Supplier::class;

    protected string $title = 'Supplier';

    protected string $singular = 'Supplier';

    protected string $routePrefix = 'master.suppliers';

    protected string $icon = 'bi bi-truck';

    protected array $searchable = ['name', 'phone', 'email'];

    protected array $columns = [
        'name' => 'Nama Supplier',
        'phone' => 'Telepon',
        'email' => 'Email',
        'address' => 'Alamat',
    ];

    public function index(Request $request)
    {
        $this->fields = [
            ['name' => 'name', 'label' => 'Nama Supplier', 'type' => 'text', 'required' => true],
            ['name' => 'phone', 'label' => 'Telepon', 'type' => 'text', 'required' => true],
            ['name' => 'email', 'label' => 'Email', 'type' => 'email'],
            ['name' => 'address', 'label' => 'Alamat', 'type' => 'textarea'],
        ];

        return parent::index($request);
    }

    protected function rules(?Model $record = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function exportHeadings(): array
    {
        return ['name', 'phone', 'email', 'address'];
    }

    protected function exportRow(Model $record): array
    {
        return [$record->name, $record->phone, $record->email, $record->address];
    }

    protected function templateRows(): array
    {
        return [
            ['CV Sumber Kopi Nusantara', '081111222333', 'supplier@sumberkopi.test', 'Jl. Raya Bogor No. 101'],
            ['Toko Bahan Kue Jaya', '081111222335', null, 'Pasar Santa Blok A-12'],
        ];
    }

    protected function importRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function importRow(array $row): void
    {
        Supplier::query()->updateOrCreate(
            ['phone' => trim((string) $row['phone'])],
            [
                'name' => trim((string) $row['name']),
                'email' => isset($row['email']) && $row['email'] !== '' ? trim((string) $row['email']) : null,
                'address' => isset($row['address']) && $row['address'] !== '' ? trim((string) $row['address']) : null,
            ],
        );
    }
}
