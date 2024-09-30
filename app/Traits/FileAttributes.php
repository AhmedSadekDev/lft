<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait FileAttributes
{
    /**
     * @return null|string
     */
    public function getImageAttribute(){
        if(isset($this->attributes['image'])){
            if(strpos($this->attributes['image'],'https') !== false ||strpos($this->attributes['image'],'http') !== false ){
                if(file_exists(public_path('public/img/'.$this->imageFolder.'/'.$this->attributes['image']))){
                    return $this->imageFolder.'/'.$this->attributes['image'];
                }else{
                    return  $this->attributes['image'];
                }
            }else{
                if(file_exists(public_path('public/img/'.$this->imageFolder.'/'.Str::snake($this->attributes['image'])))){
                    return getImg(Str::snake($this->attributes['image']),$this->imageFolder);
                }else{
                        if($this->getMorphClass() !== 'App\Models\Page')
                            return asset('/assets/img/avatar_logo.png');
                }
            }
        }else{
            if($this->getMorphClass() !== 'App\Models\Page')
                return asset('/assets/img/avatar_logo.png');
        }
    }

    /**
     * @param $value
     */
    public function setImageAttribute($value){
        if (!empty($value)){
            if (is_string($value)) {
                $this->attributes['image'] = $value;
            } else {
                $values = $value->storeAs($this->imageFolder,generateImageName($value),"public");
                $arrVal =explode('/',$values);
                $this->attributes['image']=Str::snake($arrVal[count($arrVal)-1]);
            }
        }
    }

    /**
     * @param $value
     */
    public function setLogoAttribute($value){
        if (!empty($value)){
            if (is_string($value)) {
                $this->attributes['logo'] = $value;
            } else {
                $values = $value->storeAs($this->logoFolder,generateImageName($value),"public");
                $arrVal =explode('/',$values);
                $this->attributes['logo']=Str::snake($arrVal[count($arrVal)-1]);
            }
        }
    }

    /**
     * @param $value
     */
    public function setAttachmentsAttribute($values){
        if (!empty($values)){
            if (is_string($values) && !is_array($values)) {
                $this->attributes['attachments'] = json_encode($values);
            } else {
                $attachments = [];

                foreach($values as $value){
                    $values = $value->storeAs($this->attachmentFolder, generateAttachmentName($value),"public");
                    $arrVal =explode('/',$values);
                    array_push($attachments, Str::snake($arrVal[count($arrVal)-1]));
                }

                $files = json_encode($attachments);
                $this->attributes['attachments'] = $files;
            }
        }
    }

    // public function getAttachmentsAttribute(){
    //     dd( public_path('storage/'.$this->attachmentsFolder.'/'.$this->attributes['attachments']) );
    //     if(isset($this->attributes['attachments'])){
    //         if(strpos($this->attributes['attachments'],'https') !== false ||strpos($this->attributes['attachments'],'http') !== false ){
    //             if(file_exists(public_path('storage/'.$this->attachmentsFolder.'/'.$this->attributes['attachments']))){
    //                 return $this->attachmentsFolder.'/'.$this->attributes['attachments'];
    //             }else{
    //                 return  $this->attributes['attachments'];
    //             }
    //         }else{
    //             if(file_exists(public_path('storage/'.$this->attachmentsFolder.'/'.Str::snake($this->attributes['attachments'])))){
    //                 return getAttachment(Str::snake($this->attributes['attachments']),$this->attachmentsFolder);
    //             }else{
    //                     if($this->getMorphClass() !== 'App\Models\Page')
    //                         return asset('/assets/img/avatar_logo.png');
    //             }
    //         }
    //     }else{
    //         if($this->getMorphClass() !== 'App\Models\Page')
    //             return asset('/assets/img/avatar_logo.png');
    //     }
    // }

    /**
     * @return null|string
     */
    public function getBannerAttribute(){
        if(isset($this->attributes['banner'])){
            if(strpos($this->attributes['banner'],'https') !== false ||strpos($this->attributes['banner'],'http') !== false ){
                if(file_exists(public_path('storage/'.$this->imgFolder.'/'.$this->attributes['banner']))){
                    return $this->imgFolder.'/'.$this->attributes['banner'];
                }else{
                    return  $this->attributes['banner'];
                }
            }else{
                if(file_exists(public_path('storage/'.$this->imgFolder.'/'.Str::snake($this->attributes['banner'])))){
                    return getImg(Str::snake($this->attributes['banner']),$this->imgFolder);
                }else{
                        if($this->getMorphClass() !== 'App\Models\Page')
                            return asset('/assets/img/logo.png');
                }
            }
        }else{
            if($this->getMorphClass() !== 'App\Models\Page')
                return asset('/assets/img/logo.png');
        }
    }


    /**
     * @param $value
     */
    public function setBannerAttribute($value){
        if (!empty($value)){
            if (is_string($value)) {
                $this->attributes['banner'] = $value;
            } else {
               $values = $value->storeAs($this->imgFolder,generateImageName($value),"public");
                $arrVal =explode('/',$values);
                $this->attributes['banner']=Str::snake($arrVal[count($arrVal)-1]);
            }
        }
    }


}
