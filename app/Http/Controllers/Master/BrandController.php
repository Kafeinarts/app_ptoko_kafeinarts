<?php

namespace App\Http\Controllers\Master;

use App\Models\Brand;

class BrandController extends SimpleCrudController
{
    protected string $model = Brand::class;

    protected string $title = 'Brand Produk';

    protected string $singular = 'Brand';

    protected string $routePrefix = 'master.brands';

    protected string $icon = 'bi bi-award';

    protected array $columns = ['name' => 'Nama Brand', 'created_at' => 'Dibuat'];

    protected array $fields = [
        ['name' => 'name', 'label' => 'Nama Brand', 'type' => 'text', 'required' => true],
    ];

    protected function templateRows(): array
    {
        return [['Kafeinarts'], ['Local Beans']];
    }
}
