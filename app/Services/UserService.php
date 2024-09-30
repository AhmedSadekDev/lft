<?php
namespace App\Services;


Class UserService{

    public function update($request){
        $user = auth()->user();
        $user->update($request);
        return $user;
    }
}
