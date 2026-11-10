<?php

namespace App\Http\Controllers\Master;

use App\Exports\RowsExport;
use App\Http\Controllers\Controller;
use App\Imports\RowsImport;
use App\Models\Product;
use App\Models\ProductModifier;
use App\Models\ProductModifierGroup;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ModifierController extends Controller
{
    public function index()
    {
        $groups = ProductModifierGroup::query()->with('modifiers')->withCount('products')->latest()->get();

        return view('master.modifiers', [
            'groups' => $groups,
            'products' => Product::query()->with('variants')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function storeGroup(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'is_required' => ['nullable', 'boolean'],
            'min_selection' => ['nullable', 'integer', 'min:0'],
            'max_selection' => ['nullable', 'integer', 'min:1'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['exists:products,id'],
        ]);

        try {
            $group = ProductModifierGroup::query()->create([
                'name' => $data['name'],
                'is_required' => $request->boolean('is_required'),
                'min_selection' => (int) ($data['min_selection'] ?? 0),
                'max_selection' => (int) ($data['max_selection'] ?? 1),
            ]);
            $group->products()->sync($data['product_ids'] ?? []);

            return back()->with('swal_success', 'Grup modifier berhasil ditambahkan.');
        } catch (Throwable $e) {
            return back()->with('swal_error', $e->getMessage());
        }
    }

    public function updateGroup(Request $request, ProductModifierGroup $group)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'min_selection' => ['nullable', 'integer', 'min:0'],
            'max_selection' => ['nullable', 'integer', 'min:1'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['exists:products,id'],
        ]);

        $group->update([
            'name' => $data['name'],
            'is_required' => $request->boolean('is_required'),
            'min_selection' => (int) ($data['min_selection'] ?? 0),
            'max_selection' => (int) ($data['max_selection'] ?? 1),
        ]);
        $group->products()->sync($data['product_ids'] ?? []);

        return back()->with('swal_success', 'Grup modifier diperbarui.');
    }

    public function destroyGroup(ProductModifierGroup $group)
    {
        try {
            $group->delete();

            return back()->with('swal_success', 'Grup modifier dihapus.');
        } catch (Throwable $e) {
            return back()->with('swal_error', 'Grup modifier masih dipakai transaksi.');
        }
    }

    public function storeModifier(Request $request, ProductModifierGroup $group)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);

        $group->modifiers()->create([
            'name' => $data['name'],
            'price' => $data['price'],
            'is_available' => true,
        ]);

        return back()->with('swal_success', 'Modifier berhasil ditambahkan.');
    }

    public function destroyModifier(ProductModifier $modifier)
    {
        try {
            $modifier->delete();

            return back()->with('swal_success', 'Modifier dihapus.');
        } catch (Throwable) {
            return back()->with('swal_error', 'Modifier sudah dipakai transaksi.');
        }
    }

    /* ------------------------------------------------------------------
     * Import / Export / Template massal (flat: 1 baris = 1 modifier)
     * ------------------------------------------------------------------ */

    /** @return array<int, string> */
    private function modifierExportHeadings(): array
    {
        return ['group_name', 'group_is_required', 'group_min_selection', 'group_max_selection', 'modifier_name', 'modifier_price', 'modifier_is_available'];
    }

    /** @return array<int, array<int, mixed>> */
    private function modifierExportRows(bool $withSample = false): array
    {
        if ($withSample) {
            return [
                ['Add-on Signature', 0, 0, 3, 'Extra Shot Espresso', 5000, 1],
                ['Add-on Signature', 0, 0, 3, 'Oat Milk', 8000, 1],
                ['Level Gula', 0, 0, 1, 'Normal', 0, 1],
                ['Level Gula', 0, 0, 1, 'Less Sugar', 0, 1],
            ];
        }

        $rows = [];
        foreach (ProductModifierGroup::query()->with('modifiers')->orderBy('name')->get() as $group) {
            foreach ($group->modifiers as $modifier) {
                $rows[] = [
                    $group->name,
                    $group->is_required ? 1 : 0,
                    (int) $group->min_selection,
                    (int) $group->max_selection,
                    $modifier->name,
                    (float) $modifier->price,
                    $modifier->is_available ? 1 : 0,
                ];
            }
        }

        return $rows;
    }

    public function export()
    {
        $rows = $this->modifierExportRows();

        if ($rows === []) {
            $rows = $this->modifierExportRows(true);
        }

        return Excel::download(
            new RowsExport($this->modifierExportHeadings(), $rows),
            'export-modifier-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function template()
    {
        return Excel::download(
            new RowsExport($this->modifierExportHeadings(), $this->modifierExportRows(true)),
            'template-import-modifier.xlsx'
        );
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        try {
            $import = new RowsImport([
                'group_name' => ['required', 'string', 'max:100'],
                'group_is_required' => ['nullable', 'boolean'],
                'group_min_selection' => ['nullable', 'integer', 'min:0'],
                'group_max_selection' => ['nullable', 'integer', 'min:1'],
                'modifier_name' => ['required', 'string', 'max:100'],
                'modifier_price' => ['nullable', 'numeric', 'min:0'],
                'modifier_is_available' => ['nullable', 'boolean'],
            ], function (array $row) {
                $groupName = trim((string) $row['group_name']);
                $group = ProductModifierGroup::query()->firstOrNew(['name' => $groupName]);
                $group->is_required = array_key_exists('group_is_required', $row) ? (bool) $row['group_is_required'] : $group->is_required;
                $group->min_selection = array_key_exists('group_min_selection', $row) ? (int) $row['group_min_selection'] : ($group->min_selection ?? 0);
                $group->max_selection = array_key_exists('group_max_selection', $row) ? (int) $row['group_max_selection'] : ($group->max_selection ?? 1);
                $group->save();

                ProductModifier::query()->updateOrCreate(
                    ['modifier_group_id' => $group->id, 'name' => trim((string) $row['modifier_name'])],
                    [
                        'price' => (float) ($row['modifier_price'] ?? 0),
                        'is_available' => array_key_exists('modifier_is_available', $row) ? (bool) $row['modifier_is_available'] : true,
                    ],
                );
            });
            Excel::import($import, $request->file('file'));

            $message = $import->imported.' modifier berhasil diimpor.';
            if ($import->failed > 0) {
                $message .= ' '.$import->failed.' baris gagal: '.implode(' | ', array_slice($import->errors, 0, 3));
            }

            return back()->with($import->failed > 0 && $import->imported === 0 ? 'swal_error' : 'swal_success', $message);
        } catch (Throwable $e) {
            return back()->with('swal_error', 'Import gagal: '.$e->getMessage());
        }
    }
}
