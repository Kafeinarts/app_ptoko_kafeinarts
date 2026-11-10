<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductModifier;
use App\Models\ProductModifierGroup;
use Illuminate\Http\Request;
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
}
