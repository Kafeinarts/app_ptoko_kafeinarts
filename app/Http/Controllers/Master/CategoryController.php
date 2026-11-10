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

    protected function beforeSave(array &$data, ?Model $record = null): void
    {
        $data['slug'] = Str::slug($data['name']).($record ? '-'.Str::lower(Str::random(3)) : '');
        $data['is_active'] = isset($data['is_active']) ? (bool) $data['is_active'] : true;
    }
}
