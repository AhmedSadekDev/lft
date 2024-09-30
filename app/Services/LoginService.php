<?php
namespace App\Services;


Class loginService{

    public function login($request){
        $credentials = $request;
        $token = auth()->guard('api')->attempt($credentials);
        if ( $token )
        {
            // -------- JWT --------
            $company = auth()->guard('api')->user();
            $forever = true;
            $token = \JWTAuth::fromUser($company);

            // if(!is_null($user->session_id)){
            //     dd('test');
            //     \JWTAuth::manager()->invalidate(
            //         new \Tymon\JWTAuth\Token($company->session_id),
            //         $forever
            //     );
            // }

            $company->update(array_merge(['session_id' => $token]));

            return $company;
        }
    }
}
