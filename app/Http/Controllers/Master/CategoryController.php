<?php

namespace App\Http\Controllers\Master;

use App\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends SimpleCrudController
{
    protected string $model = Category::class;

    protected string $title = 'Kategori Produk';

    protected string $singular = 'Kategori';

    protected string $routePrefix = 'master.categories';

    protected string $icon = 'bi bi-tags';

    protected array $columns = [
        'name' => 'Nama Kategori',
        'slug' => 'Slug',
        'is_active' => 'Status',
    ];

    protected array $fields = [
        ['name' => 'name', 'label' => 'Nama Kategori', 'type' => 'text', 'required' => true],
        ['name' => 'is_active', 'label' => 'Aktif', 'type' => 'checkbox', 'default' => true],
    ];

    protected function rules(?Model $record = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function exportHeadings(): array
    {
        return ['name', 'is_active'];
    }

    protected function exportRow(Model $record): array
    {
        return [$record->name, $record->is_active ? 1 : 0];
    }

    protected function templateRows(): array
    {
        return [['Kopi', 1], ['Makanan', 1]];
    }

    protected function importRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function importRow(array $row): void
    {
        $name = trim((string) $row['name']);
        $record = Category::query()->firstOrNew(['name' => $name]);
        if (! $record->exists) {
            $record->slug = Str::slug($name).'-'.Str::lower(Str::random(4));
        }
        $record->is_active = array_key_exists('is_active', $row) ? (bool) $row['is_active'] : true;
        $record->save();
    }

    protected function beforeSave(array &$data, ?Model $record = null): void
    {
        $data['slug'] = Str::slug($data['name']).($record ? '-'.Str::lower(Str::random(3)) : '');
        $data['is_active'] = isset($data['is_active']) ? (bool) $data['is_active'] : true;
    }
}
