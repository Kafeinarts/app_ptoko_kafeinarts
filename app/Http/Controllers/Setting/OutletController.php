<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\User;
use App\Support\OutletContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class OutletController extends Controller
{
    public function index()
    {
        $outlets = Outlet::query()->with('manager')->withCount('users')->orderBy('name')->get();
        $managers = User::query()->where('is_active', true)->with('role')->orderBy('name')->get();

        return view('setting.outlets', compact('outlets', 'managers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:outlets,code'],
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'receipt_footer' => ['nullable', 'string', 'max:255'],
            'manager_id' => ['nullable', 'exists:users,id'],
        ]);

        try {
            $data['is_active'] = $request->boolean('is_active', true);
            Outlet::query()->create($data);

            return back()->with('swal_success', 'Outlet berhasil ditambahkan.');
        } catch (Throwable $e) {
            return back()->withInput()->with('swal_error', $e->getMessage());
        }
    }

    public function update(Request $request, Outlet $outlet)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('outlets', 'code')->ignore($outlet->id)],
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'receipt_footer' => ['nullable', 'string', 'max:255'],
            'manager_id' => ['nullable', 'exists:users,id'],
        ]);

        try {
            $data['is_active'] = $request->boolean('is_active', true);
            $outlet->update($data);

            return back()->with('swal_success', 'Outlet diperbarui.');
        } catch (Throwable $e) {
            return back()->withInput()->with('swal_error', $e->getMessage());
        }
    }

    public function switch(Request $request)
    {
        $data = $request->validate(['outlet_id' => ['required', 'exists:outlets,id']]);
        OutletContext::set((int) $data['outlet_id']);

        return back()->with('swal_success', 'Outlet aktif berhasil diganti.');
    }

    public function destroy(Outlet $outlet)
    {
        if (Outlet::query()->count() <= 1) {
            return back()->with('swal_error', 'Minimal harus ada satu outlet.');
        }

        try {
            $outlet->update(['is_active' => false]);

            return back()->with('swal_success', 'Outlet dinonaktifkan.');
        } catch (Throwable) {
            return back()->with('swal_error', 'Outlet tidak dapat dinonaktifkan.');
        }
    }
}
