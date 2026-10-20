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
            $size = $matches[1];
            $type = trim($matches[2]);
            $type = trim(preg_replace('/^-\s*/u', '', $type) ?? $type);

            $exact = static::query()
                ->where('size', $size)
                ->whereRaw('LOWER(TRIM(type)) = ?', [mb_strtolower($type, 'UTF-8')])
                ->first();
            if ($exact) {
                return $exact;
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
                return $like->first(fn (self $c) => mb_strtolower(trim((string) $c->type), 'UTF-8') === mb_strtolower($type, 'UTF-8'))
                    ?? $like->first();
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

    public function transportations(){
        return $this->hasMany(CompanyTransportation::class);
    }
}
