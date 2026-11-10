<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class UserController extends Controller
{
    public function index()
    {
        $users = User::query()->with(['role', 'outlet'])->latest()->paginate(15);
        $roles = Role::query()->orderBy('name')->get();
        $outlets = Outlet::query()->orderBy('name')->get();

        return view('setting.users', compact('users', 'roles', 'outlets'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role_id' => ['required', 'exists:roles,id'],
            'outlet_id' => ['nullable', 'exists:outlets,id'],
            'password' => ['required', 'string', 'min:6'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        try {
            $data['is_active'] = $request->boolean('is_active', true);
            User::query()->create($data);

            return back()->with('swal_success', 'Pengguna berhasil ditambahkan.');
        } catch (Throwable $e) {
            return back()->withInput()->with('swal_error', $e->getMessage());
        }
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'role_id' => ['required', 'exists:roles,id'],
            'outlet_id' => ['nullable', 'exists:outlets,id'],
            'password' => ['nullable', 'string', 'min:6'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        try {
            $data['is_active'] = $request->boolean('is_active', true);
            if (empty($data['password'])) {
                unset($data['password']);
            }
            $user->update($data);

            return back()->with('swal_success', 'Pengguna berhasil diperbarui.');
        } catch (Throwable $e) {
            return back()->withInput()->with('swal_error', $e->getMessage());
        }
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            return back()->with('swal_error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        try {
            $user->delete();

            return back()->with('swal_success', 'Pengguna dihapus.');
        } catch (Throwable) {
            return back()->with('swal_error', 'Pengguna memiliki data transaksi sehingga tidak dapat dihapus. Nonaktifkan saja.');
        }
    }
}
