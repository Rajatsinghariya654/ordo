<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key_name',
        'key_value',
        'updated_by',
    ];

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Helper: seedha value nikaalne ke liye
    // Usage: SystemSetting::getValue('default_gps_radius')
    public static function getValue(string $key, $default = null)
    {
        return static::where('key_name', $key)->value('key_value') ?? $default;
    }

    // Helper: value save/update karne ke liye
    // Usage: SystemSetting::setValue('default_gps_radius', '5', auth()->id())
    public static function setValue(string $key, $value, ?int $updatedBy = null): void
    {
        static::updateOrCreate(
            ['key_name' => $key],
            ['key_value' => $value, 'updated_by' => $updatedBy]
        );
    }
}
