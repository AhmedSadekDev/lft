<?php

use Carbon\Carbon;
use App\Models\Company;
use App\Models\Factory;
use App\Models\Container;
use Illuminate\Support\Str;
use App\Enums\BookingActionsEnum;
use Illuminate\Support\Facades\DB;

if(!function_exists('companies')){
    /**
     * @return mixed
     */
    function companies($id = null){

        $companies = Company::where('id', )->pluck('name','id');
        return $companies;
    }
}

if(!function_exists('bookingActions')){
    /**
     * @return mixed
     */
    function bookingActions(){
        $actions = BookingActionsEnum::getKeys();
        $bookingActions = collect($actions)->map(function($action){
                return __('actions.'.$action);
        });
        return $bookingActions;
    }
}

if(!function_exists('TypeOfAction')){
    /**
     * @return mixed
     */
    function TypeOfAction($type){

        switch ($type) {
            case '0':
                # code...
                return 'Outbound';
                break;
            case '1':
                # code...
                return 'Inbound';
                break;
            case '2':
                # code...
                return 'Clearance';
                break;
            default:
                # code...
                return '__';
                break;
        }
    }
}

if(!function_exists('serviceStatus')){
    /**
     * @return mixed
     */
    function serviceStatus($type){

        switch ($type) {
            case '0':
                # code...
                return __('admin.taxed');
                break;
            case '1':
                # code...
                return __('admin.untaxed');
                break;
            case '2':
                # code...
                return __('admin.not_added');
                break;
            default:
                # code...
                return '__';
                break;
        }
    }
}


if(!function_exists('adminDbTablesPermissions')){
    /**
     * @return mixed
     */
    function adminDbTablesPermissions(){
        $adminPermissions = [
            'companies',
            'superagents',
            'agents',
            'drivers',
            'cars',
            'employees',
            'factories',
            'branches',
            'cities',
            'containers',
            'serviceCategories',
            'services',
            'shippingAgents',
            'staticPages',
            'ourServices',
            'chooseUs',
            'sponsers',
            'reviews',
            'settings',
            'contactUs',
            'users',
            'bookings',
            'yards',
            'daily_reports',
            'financial_custody_agents',
            'permissions',
            'transportations',
            'shipments',
            'vaults',
            'banks',
        ];


        return $adminPermissions;
    }
}

if(!function_exists('generateImageName')){
    /**
     * @param $file
     * @return string
     */
    function generateImageName($file){
        $fileNameWithExt = $file->getClientOriginalName();
        $filename = pathinfo($fileNameWithExt, PATHINFO_FILENAME);
        $extention = $file->getClientOriginalExtension();
        $fileNameToStore = $filename.'_'.time().'.'.$extention;
        return Str::snake($fileNameToStore);
    }
}

if(!function_exists('generateAttachmentName')){
    /**
     * @param $file
     * @return string
     */
    function generateAttachmentName($file){
        $fileNameWithExt = $file->getClientOriginalName();
        $filename = pathinfo($fileNameWithExt, PATHINFO_FILENAME);
        $extention = $file->getClientOriginalExtension();
        $fileNameToStore = $filename.'_'.time().'.'.$extention;
        return Str::snake($fileNameToStore);
    }
}

if(!function_exists('getImg')){
    /**
     * @param $filename
     * @return string
     */
    function getImg($filename,$imageFolder){

        if (!empty($filename)) {
            $base_url = url('/');
            return $base_url . '/storage/' .$imageFolder. '/'. $filename;
        } else {
            return '';
        }
    }

}

if (!function_exists('upload_file')) {

    function upload_file($file, $path, $quality = 50)
    {
        $base_path = public_path("/uploads/$path");

        if (!file_exists($base_path)) {
            mkdir($base_path, 0777, true);
        }

        $str = mt_rand();
        $result = md5($str);
        // $file_original_name = $file->getClientOriginalName();
        // $file_name = time() . $result . $file_original_name;
        $file_name = time() . $result . '.png';

        $file_ext = $file->getClientOriginalExtension();

        $file_path = "public/uploads/$path/$file_name";
        $file->move($base_path, $file_name);
        return [
            'file_name' => $file_name,
            'file_path' => $file_path,
            'file_ext'  => $file_ext,
        ];
    }
}

if(!function_exists('getAttachment')){
    /**
     * @param $filename
     * @return string
     */
    function getAttachment($filename,$attachmentFolder){

        if (!empty($filename)) {
            // $base_url = storage_path('/');
            // return $base_url . '/app/public/' .$attachmentFolder. '/'. $filename;
            return asset('/storage/'.$attachmentFolder . '/' . $filename);
        } else {
            return '';
        }
    }

}

if(!function_exists('containerType')){
    /**
     * @param $filename
     * @return string
     */
    function containerType($id){
        if(!is_null($id)){
            $type = Container::find($id);
            return $type->type;
        }
    }

}

if(!function_exists('factoryBranches')){
    /**
     * @param $filename
     * @return string
     */
    function factoryBranches($id){
        if(!is_null($id)){
            $factory = Factory::find($id);
            return $factory->branches?->pluck('name', 'id');
        }
    }

}

if(!function_exists('invoiceNumberTrim')){
    /**
     * @param $filename
     * @return string
     */
    function invoiceNumberTrim($id){
        return str_pad($id, 3, '0', STR_PAD_LEFT);
    }
}


if(!function_exists('trim_key')){
    /**
     * @param $filename
     * @return string
     */
    function trim_key($key){
        return strtoupper(Str::snake($key));
    }
}

if (!function_exists('custom_size_base64')) {
    function custom_size_base64($base64_string, $WIDTH, $HEIGHT)
    {
        $QUALITY = 100; //The quality of your new image

        $image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64_string));
        $size = \getimagesizefromstring($image);
        $org_w = $size[0];
        $org_h = $size[1];
        $theme_image_little = imagecreatefromstring($image);
        $image_little = imagecreatetruecolor($WIDTH, $HEIGHT);
        // $org_w and org_h depends of your image, in your case, i guess 800 and 600
        imagecopyresampled($image_little, $theme_image_little, 0, 0, 0, 0, $WIDTH, $HEIGHT, $org_w, $org_h);

        // Thanks to Michael Robinson
        // start buffering
        ob_start();
        imagepng($image_little);
        $contents = ob_get_clean();

        return base64_encode($contents);
    }
}





