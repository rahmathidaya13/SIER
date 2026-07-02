<?php

namespace App\Traits;

use Illuminate\Validation\ValidationException;

trait ImmutableFieldValidator
{
    /**
     * Memastikan field tertentu tidak boleh berubah
     *
     * @param object $model // Model yang sedang diperiksa
     * @param array $newData // Data baru yang akan digunakan untuk memperbarui model
     * @param array $rules // Aturan validasi untuk field yang tidak boleh berubah
     * @throws ValidationException // Jika ada field yang tidak boleh berubah namun nilainya berbeda dari nilai saat ini
     * @return void
     */
    public static function validate(object $model, array $newData, array $rules): void
    {
        foreach ($rules as $field => $rule) {

            $currentValue = data_get($model, $field); // Mengambil nilai saat ini dari model
            $newValue = $newData[$field] ?? null; // Mengambil nilai baru dari data / request yang diberikan

            $lockedValue = $rule['locked_value']; // Nilai yang tidak boleh diubah atau parameter yang ditentukan dalam aturan

            // Jika nilai saat ini sama dengan nilai yang terkunci dan nilai baru berbeda, maka lemparkan exception validasi
            // Gunakan error_key jika disediakan, jika tidak gunakan nama field sebagai kunci error dari aturan validasi sebagai default
            if ($currentValue === $lockedValue && $newValue !== $lockedValue) {
                throw ValidationException::withMessages([
                    $rule['error_key'] ?? $field => $rule['message'] // Pesan error yang ditentukan dalam aturan
                ]);
            }
        }
    }
}
