<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

abstract class SimpleCrudController extends Controller
{
    protected string $model;

    protected string $title;

    protected string $singular;

    protected string $routePrefix;

    protected string $icon = 'bi bi-collection';

    protected array $columns = [];

    protected array $fields = [];

    protected array $searchable = ['name'];

    protected function rules(?Model $record = null): array
    {
        return ['name' => ['required', 'string', 'max:100', Rule::unique((new $this->model)->getTable(), 'name')->ignore($record?->id)]];
    }

    public function index(Request $request)
    {
        $query = $this->model::query();

        if ($request->filled('q')) {
            $term = $request->get('q');
            $query->where(function ($q) use ($term) {
                foreach ($this->searchable as $column) {
                    $q->orWhere($column, 'like', "%{$term}%");
                }
            });
        }

        $records = $query->latest()->paginate(15)->withQueryString();

        return view('master.simple', [
            'records' => $records,
            'title' => $this->title,
            'singular' => $this->singular,
            'routePrefix' => $this->routePrefix,
            'icon' => $this->icon,
            'columns' => $this->columns,
            'fields' => $this->fields,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        try {
            $this->beforeSave($data);
            $this->model::query()->create($data);

            return back()->with('swal_success', $this->singular.' berhasil ditambahkan.');
        } catch (Throwable $e) {
            return back()->withInput()->with('swal_error', $e->getMessage());
        }
    }

    public function update(Request $request, int $id)
    {
        $record = $this->model::query()->findOrFail($id);
        $data = $request->validate($this->rules($record));

        try {
            $this->beforeSave($data, $record);
            $record->update($data);

            return back()->with('swal_success', $this->singular.' berhasil diperbarui.');
        } catch (Throwable $e) {
            return back()->withInput()->with('swal_error', $e->getMessage());
        }
    }

    public function destroy(int $id)
    {
        $record = $this->model::query()->findOrFail($id);

        try {
            $record->delete();

            return back()->with('swal_success', $this->singular.' berhasil dihapus.');
        } catch (Throwable $e) {
            return back()->with('swal_error', $this->singular.' tidak dapat dihapus karena masih dipakai data lain.');
        }
    }

    protected function beforeSave(array &$data, ?Model $record = null): void {}
}
