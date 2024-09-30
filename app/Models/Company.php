<?php

namespace App\Models;

use App\Traits\FileAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class Company extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasRoles, HasFactory, Notifiable, FileAttributes, CanResetPassword;

    protected $attachmentFolder = 'company';

    protected $fillable = [
        'name',
        'email',
        'address',
        'phone',
        'tax_no',
        'taxed',
        'password',
        'session_id',
        'bill_type',
        'attachments',
        'invoice_number_auto_increment',
        'wallet'
    ];

    protected $casts = [
        'attachments' => 'array'
    ];

    protected $hidden = [
        'password',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */
    public function containers()
    {
        return $this->hasMany(Container::class);
    }

    public function transportations()
    {
        return $this->hasMany(CompanyTransportation::class);
    }


    /**
     * Get all of the comments for the Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Get all of the comments for the Company
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function services()
    {
        return $this
            ->belongsToMany(
                Service::class,
                'company_services',
                'company_id',
                'service_id'
            )
            ->withPivot(['cost'])
            ->withTimestamps();
    }

    // public function getBillTypeAttribute($value)
    // {
    //     return $value == 1 ? __('admin.bill_type_invoice') : __('admin.bill_type_statement');
    // }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function getCreatedAtAttribute($value)
    {
        return date('Y-m-d', strtotime($value));
    }

    public function getInvoiceNumberAttribute($value)
    {
        return $this->invoice_number_auto_increment;
    }

    public function getUpdatedAtAttribute($value)
    {
        return date('Y-m-d', strtotime($value));
    }

    public function getAttachmentsAttribute($value)
    {
        if (json_decode($value) > 0) {
            $attachments = [];
            if (is_array(json_decode($value))) {
                foreach (json_decode($value) as $attach) {
                    array_push($attachments, getAttachment(Str::snake($attach), $this->attachmentFolder));
                }
            } else {
                $attachments = !is_null($value) ? getAttachment(Str::snake($value), $this->attachmentFolder) : null;
            }
        } else {
            $attachments = !is_null($value) ? getAttachment(Str::snake($value), $this->attachmentFolder) : null;
        }
        return $attachments;
    }

    public function getTaxedInvoiceAttribute($value)
    {
        return ($this->taxed == 0 ? __('admin.no') : __('admin.yes'));
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getImageAttribute($value)
    {
        return asset('/storage/' . $this->imageFolder . '/' . $value);
    }
    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */

    public function setAttachmentsAttribute($values)
    {
        if (!empty($values)) {
            // Retrieve existing attachments from the database

            $existingAttachments = $this->attributes['attachments'] ?? null;

            if ($existingAttachments !== null) {
                $existingAttachments = json_decode($existingAttachments, true) ?? [];
            } else {
                $existingAttachments = [];
            }

            if (is_string($values) && !is_array($values)) {
                // Convert the incoming string to an array and merge with existing attachments
                $newAttachments = array_merge($existingAttachments, json_decode($values, true));
                $this->attributes['attachments'] = json_encode($newAttachments);
            } else {
                $attachments = [];

                foreach ($values as $value) {
                    $uploadedFile = $value->storeAs($this->attachmentFolder, generateAttachmentName($value), "public");
                    $arrVal = explode('/', $uploadedFile);
                    array_push($attachments, Str::snake($arrVal[count($arrVal) - 1]));
                }

                // Merge new attachments with existing attachments
                $combinedAttachments = array_merge($existingAttachments, $attachments);

                $files = json_encode($combinedAttachments);
                $this->attributes['attachments'] = $files;
            }
        }
    }


    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = !is_null($password) ? Hash::make($password) : (!is_null($this->password) ? $this->password : null);
    }

    public function invoices()
    {
        return $this->hasManyThrough(Invoice::class, Booking::class);
    }
    public function companyInvoices()
    {
        return $this->hasMany(companyInvoices::class);
    }
}
