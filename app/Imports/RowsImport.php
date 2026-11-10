<?php

namespace App\Imports;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Throwable;

/**
 * Import massal generik berbasis rules + closure per baris.
 * Baris gagal tidak menghentikan proses — dikumpulkan untuk dilaporkan.
 */
class RowsImport implements ToCollection, WithHeadingRow
{
    public int $imported = 0;

    public int $failed = 0;

    /** @var array<int, string> */
    public array $errors = [];

    /**
     * @param  array<string, mixed>  $rules
     * @param  Closure(array<string, mixed> $row): void  $persister
     */
    public function __construct(
        private readonly array $rules,
        private readonly Closure $persister,
    ) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNumber = (int) $index + 2; // baris 1 = heading

            try {
                /** @var array<string, mixed> $normalized */
                $normalized = $row->mapWithKeys(fn ($value, $key) => [Str::slug((string) $key, '_') => $value])->all();
                $validated = Validator::make($normalized, $this->rules)->validate();
                ($this->persister)($validated);
                $this->imported++;
            } catch (ValidationException $e) {
                $this->failed++;
                $this->errors[] = "Baris {$rowNumber}: ".collect($e->errors())->flatten()->first();
            } catch (Throwable $e) {
                $this->failed++;
                $this->errors[] = "Baris {$rowNumber}: {$e->getMessage()}";
            }
        }
    }
}
