<?php

namespace App\Models;

use App\Traits\FileAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Factory extends Model
{
    use HasFactory, FileAttributes;
    protected $attachmentFolder = 'factories';

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'attachments',
        'number'
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function getAttachmentsAttribute($value)
    {
        $files = !is_null($value) ? json_decode($value) : null;
        if( is_array($files) & !is_null($files) ){
            $attachments = [];
            foreach($files as $attachment){
                array_push($attachments, asset('/storage/'.$this->attachmentFolder . '/' . $attachment));
            }
            return $attachments;
        }else{
            return !is_null($value) ? asset('/storage/'.$this->attachmentFolder . '/' . $value) : null;
        }
    }

}
