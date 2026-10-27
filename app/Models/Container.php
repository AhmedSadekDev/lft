<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Container extends Model
{
    use HasFactory;
    protected $table = 'containers';

    protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];

    protected $fillable = [
        'type',
        'size',
    ];

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }

    public function getFullNameAttribute()
    {
        return "$this->size - $this->type";
    }

    /**
     * Resolve a container from Excel / free-text labels.
     * Accepts: "هاي كيوب", "40 هاي كيوب", "40 - هاي كيوب", "40-هاي كيوب".
     */
    public static function findByImportLabel(?string $label): ?self
    {
        $label = trim(preg_replace('/\s+/u', ' ', (string) $label) ?? '');
        if ($label === '') {
            return null;
        }

        $normalized = mb_strtolower(str_replace(['–', '—'], '-', $label), 'UTF-8');
        $normalized = trim(preg_replace('/\s*-\s*/u', ' - ', $normalized) ?? $normalized);
        $normalized = trim(preg_replace('/\s+/u', ' ', $normalized) ?? $normalized);

        // Exact full_name: "40 - هاي كيوب"
        $byFullName = static::query()->get()->first(function (self $container) use ($normalized) {
            $full = mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $container->full_name) ?? ''), 'UTF-8');
            $full = trim(preg_replace('/\s*-\s*/u', ' - ', $full) ?? $full);

            return $full === $normalized;
        });
        if ($byFullName) {
            return $byFullName;
        }

        // "40 هاي كيوب" / "40-هاي كيوب" → size + type
        if (preg_match('/^(\d+)\s*(?:-\s*)?(.+)$/u', $normalized, $matches)) {
            $found = static::resolveBySizeAndType($matches[1], trim($matches[2]));
            if ($found) {
                return $found;
            }
        }

        // "HC - 40" / "هاي كيوب - 40" / "HC-40" → type + size (export / Excel style)
        if (preg_match('/^(.+?)\s*-\s*(\d+)$/u', $normalized, $matches)
            || preg_match('/^(.+?)\s+(\d+)$/u', $normalized, $matches)) {
            $found = static::resolveBySizeAndType($matches[2], trim($matches[1]));
            if ($found) {
                return $found;
            }
        }

        // Type only (manual UI style): "هاي كيوب" — unique type preferred
        $exactType = static::query()
            ->whereRaw('LOWER(TRIM(type)) = ?', [mb_strtolower($label, 'UTF-8')])
            ->orderBy('id')
            ->get();
        if ($exactType->count() === 1) {
            return $exactType->first();
        }
        if ($exactType->count() > 1) {
            // Ambiguous size: prefer 40 then first
            return $exactType->first(fn (self $c) => (string) $c->size === '40') ?? $exactType->first();
        }

        $typeLike = static::query()
            ->where('type', 'LIKE', '%' . $label . '%')
            ->orderBy('id')
            ->get();
        if ($typeLike->count() === 1) {
            return $typeLike->first();
        }
        if ($typeLike->count() > 1) {
            return $typeLike->first(fn (self $c) => (string) $c->size === '40') ?? $typeLike->first();
        }

        return null;
    }

    private static function resolveBySizeAndType(string $size, string $type): ?self
    {
        $size = trim($size);
        $type = trim(preg_replace('/^-\s*/u', '', trim($type)) ?? trim($type));
        if ($size === '' || $type === '') {
            return null;
        }

        $exact = static::query()
            ->where('size', $size)
            ->whereRaw('LOWER(TRIM(type)) = ?', [mb_strtolower($type, 'UTF-8')])
            ->first();
        if ($exact) {
            return $exact;
        }

        // Also try swapped columns (legacy rows may store "HC" in size and "40" in type).
        $swapped = static::query()
            ->where('size', $type)
            ->whereRaw('LOWER(TRIM(type)) = ?', [mb_strtolower($size, 'UTF-8')])
            ->first();
        if ($swapped) {
            return $swapped;
        }

        $like = static::query()
            ->where('size', $size)
            ->where('type', 'LIKE', '%' . $type . '%')
            ->orderBy('id')
            ->get();
        if ($like->count() === 1) {
            return $like->first();
        }
        if ($like->count() > 1) {
            return $like->first(
                fn (self $c) => mb_strtolower(trim((string) $c->type), 'UTF-8') === mb_strtolower($type, 'UTF-8')
            ) ?? $like->first();
        }

        return null;
    }

    public function transportations(){
        return $this->hasMany(CompanyTransportation::class);
    }
}
