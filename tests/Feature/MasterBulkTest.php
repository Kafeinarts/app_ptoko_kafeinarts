<?php

namespace Tests\Feature;

use App\Exports\RowsExport;
use App\Models\Category;
use App\Models\Customer;
use App\Models\ProductModifierGroup;
use App\Models\Supplier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class MasterBulkTest extends TestCase
{
    /**
     * Buat file xlsx di disk lokal lalu jadikan UploadedFile untuk diunggah.
     *
     * @param  array<int, string>  $headings
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function fakeXlsx(array $headings, array $rows): UploadedFile
    {
        $name = 'bulk-test-'.uniqid().'.xlsx';
        Excel::store(new RowsExport($headings, $rows), $name, 'local');
        $path = Storage::disk('local')->path($name);

        return new UploadedFile($path, $name, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    public function test_categories_export_template_import(): void
    {
        $this->actingAsSeeded();

        $this->get(route('master.categories.export'))
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->get(route('master.categories.template'))->assertOk();

        $file = $this->fakeXlsx(['name', 'is_active'], [['Kategori Bulk Test', 1]]);
        $this->post(route('master.categories.import'), ['file' => $file])->assertSessionHas('swal_success');

        $this->assertDatabaseHas('categories', ['name' => 'Kategori Bulk Test', 'is_active' => true]);
    }

    public function test_brands_export_template_import(): void
    {
        $this->actingAsSeeded();

        $this->get(route('master.brands.export'))->assertOk();
        $this->get(route('master.brands.template'))->assertOk();

        $file = $this->fakeXlsx(['name'], [['Brand Bulk Test']]);
        $this->post(route('master.brands.import'), ['file' => $file])->assertSessionHas('swal_success');

        $this->assertDatabaseHas('brands', ['name' => 'Brand Bulk Test']);
    }

    public function test_units_export_template_import(): void
    {
        $this->actingAsSeeded();

        $this->get(route('master.units.export'))->assertOk();
        $this->get(route('master.units.template'))->assertOk();

        $file = $this->fakeXlsx(['name', 'short_name'], [['Batch', 'bch']]);
        $this->post(route('master.units.import'), ['file' => $file])->assertSessionHas('swal_success');

        $this->assertDatabaseHas('units', ['name' => 'Batch', 'short_name' => 'bch']);
    }

    public function test_customers_export_template_import(): void
    {
        $this->actingAsSeeded();

        $this->get(route('master.customers.export'))->assertOk();
        $this->get(route('master.customers.template'))->assertOk();

        $file = $this->fakeXlsx(
            ['name', 'phone', 'email', 'customer_group', 'loyalty_points'],
            [['Pelanggan Bulk Test', '081299900001', 'bulk@example.test', 'Member', 25]],
        );
        $this->post(route('master.customers.import'), ['file' => $file])->assertSessionHas('swal_success');

        $customer = Customer::query()->where('phone', '081299900001')->firstOrFail();
        $this->assertSame('Pelanggan Bulk Test', $customer->name);
        $this->assertSame('Member', $customer->group?->name);
        $this->assertSame(25, $customer->loyalty_points);
    }

    public function test_suppliers_export_template_import(): void
    {
        $this->actingAsSeeded();

        $this->get(route('master.suppliers.export'))->assertOk();
        $this->get(route('master.suppliers.template'))->assertOk();

        $file = $this->fakeXlsx(
            ['name', 'phone', 'email', 'address'],
            [['Supplier Bulk Test', '081299900002', 'bulk@supplier.test', 'Jl. Import No. 1']],
        );
        $this->post(route('master.suppliers.import'), ['file' => $file])->assertSessionHas('swal_success');

        $this->assertDatabaseHas('suppliers', ['phone' => '081299900002', 'name' => 'Supplier Bulk Test']);
    }

    public function test_modifiers_export_template_import(): void
    {
        $this->actingAsSeeded();

        $this->get(route('master.modifiers.export'))->assertOk();
        $this->get(route('master.modifiers.template'))->assertOk();

        $file = $this->fakeXlsx(
            ['group_name', 'group_is_required', 'group_min_selection', 'group_max_selection', 'modifier_name', 'modifier_price', 'modifier_is_available'],
            [
                ['Grup Bulk', 1, 1, 1, 'Opsi Bulk Satu', 7000, 1],
                ['Grup Bulk', 1, 1, 1, 'Opsi Bulk Dua', 8000, 1],
            ],
        );
        $this->post(route('master.modifiers.import'), ['file' => $file])->assertSessionHas('swal_success');

        $group = ProductModifierGroup::query()->where('name', 'Grup Bulk')->firstOrFail();
        $this->assertTrue($group->is_required);
        $this->assertEquals(1, $group->modifiers()->where('name', 'Opsi Bulk Satu')->count());
        $this->assertEquals(1, $group->modifiers()->where('name', 'Opsi Bulk Dua')->count());

        // Re-import file yang sama tidak boleh menduplikasi data.
        $file = $this->fakeXlsx(
            ['group_name', 'group_is_required', 'group_min_selection', 'group_max_selection', 'modifier_name', 'modifier_price', 'modifier_is_available'],
            [['Grup Bulk', 1, 1, 1, 'Opsi Bulk Satu', 9000, 1]],
        );
        $this->post(route('master.modifiers.import'), ['file' => $file])->assertSessionHas('swal_success');
        $this->assertSame(1, $group->modifiers()->where('name', 'Opsi Bulk Satu')->count());
        $this->assertEquals(9000.0, (float) $group->modifiers()->where('name', 'Opsi Bulk Satu')->value('price'));
    }

    public function test_kasir_cannot_use_master_bulk_routes(): void
    {
        $this->actingAsSeeded('kasir@kafeinarts.test');

        $this->get(route('master.categories.export'))->assertForbidden();
        $this->post(route('master.categories.import'), [])->assertForbidden();
        $this->get(route('master.modifiers.export'))->assertForbidden();
        $this->post(route('master.modifiers.import'), [])->assertForbidden();
    }
}
