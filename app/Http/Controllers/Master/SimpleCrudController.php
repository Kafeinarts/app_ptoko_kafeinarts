<?php

namespace App\Http\Controllers\Master;

use App\Exports\RowsExport;
use App\Http\Controllers\Controller;
use App\Imports\RowsImport;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
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

    protected function formFields(): array
    {
        return $this->fields;
    }

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
            'fields' => $this->formFields(),
        ]);
    }

    public function create()
    {
        return view('master.insert', [
            'title' => $this->title,
            'singular' => $this->singular,
            'routePrefix' => $this->routePrefix,
            'icon' => $this->icon,
            'fields' => $this->formFields(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        try {
            $this->beforeSave($data);
            $this->model::query()->create($data);

            return redirect()->route($this->routePrefix.'.index')
                ->with('swal_success', $this->singular.' berhasil ditambahkan.');
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

    /* ------------------------------------------------------------------
     * Import / Export / Template massal
     * ------------------------------------------------------------------ */

    /** @return array<int, string> */
    protected function exportHeadings(): array
    {
        return ['name'];
    }

    /** @return array<int, mixed> */
    protected function exportRow(Model $record): array
    {
        return [$record->name];
    }

    /** @return array<int, array<int, mixed>> */
    protected function templateRows(): array
    {
        return [['Contoh Data']];
    }

    /** @return array<string, mixed> */
    protected function importRules(): array
    {
        return ['name' => ['required', 'string', 'max:100']];
    }

    protected function importRow(array $row): void
    {
        $this->model::query()->updateOrCreate(['name' => trim((string) $row['name'])], []);
    }

    protected function importSlug(): string
    {
        return Str::afterLast($this->routePrefix, '.');
    }

    public function export()
    {
        $rows = $this->model::query()->orderBy('name')->get()->map(fn (Model $record) => $this->exportRow($record))->all();

        return Excel::download(
            new RowsExport($this->exportHeadings(), $rows),
            'export-'.$this->importSlug().'-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function template()
    {
        return Excel::download(
            new RowsExport($this->exportHeadings(), $this->templateRows()),
            'template-import-'.$this->importSlug().'.xlsx'
        );
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        try {
            $import = new RowsImport($this->importRules(), fn (array $row) => $this->importRow($row));
            Excel::import($import, $request->file('file'));

            $message = $import->imported.' '.$this->singular.' berhasil diimpor.';
            if ($import->failed > 0) {
                $message .= ' '.$import->failed.' baris gagal: '.implode(' | ', array_slice($import->errors, 0, 3));
            }

            return back()->with($import->failed > 0 && $import->imported === 0 ? 'swal_error' : 'swal_success', $message);
        } catch (Throwable $e) {
            return back()->with('swal_error', 'Import gagal: '.$e->getMessage());
        }
    }
}
