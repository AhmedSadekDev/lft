<?php

namespace App\Models;

use App\Traits\FileAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class BookingService extends Model
{
    use HasFactory, FileAttributes;
    protected $imageFolder = 'services';
    protected $fillable = [
        'booking_id',
        'service_id',
        'note',
        'price',
        'image',
        'service_data', // TODO: HANDLE WHEN THESE SHLD BE ADDED
        'vault_id',
        'bank_id',
        'payment_type', // vault, bank, agent
        'agent_id',
        'created_by',
        'updated_by',
    ];

    /**
     * رابط صورة الإيصال (مجلد public disk: storage/app/public/services)
     */
    public function getImageAttribute($value): ?string
    {
        $filename = $value ?? $this->attributes['image'] ?? null;
        if ($filename === null || $filename === '') {
            return null;
        }
        if (is_string($filename) && preg_match('#^https?://#i', $filename)) {
            return $filename;
        }
        $folder = trim($this->imageFolder ?? 'services', '/');
        $name = basename(str_replace('\\', '/', $filename));
        $relative = $folder . '/' . $name;

        return asset('storage/' . $relative);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /** DEPRECATED: Get it through the service.service_category instead */
    public function serviceCategory()
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    public function getFullNameAttribute()
    {
        return $this->service->name
            . ' '
            . $this->service->serviceCategory->title;
    }

    public function vault()
    {
        return $this->belongsTo(Vault::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }
}
