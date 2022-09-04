<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Users;
use Illuminate\Support\Facades\Validator;

use App\Http\Controllers\AuthController;

class UserController extends Controller
{
    public function profile(Request $request, $id, $type='posts'){
        $user = Users::where(['id' => $id])->first();
        if($user === null){
            abort(404);
        }

        $types = ['posts', 'comments'];

        if(!in_array($type, $types)){
            return [
                'action' => 'error',
                'data' => 'Действие запрещенно'
            ];
        }

        return view('userprofile')->with('user', $user)->with('type', $type);
    }

    public function getByID(Request $request){
        $validate = Validator::make($request->all(), [
            'ids' => 'required|array|max:1000',
            'ids.*' => 'required|integer'
        ],[
            'ids.required' => 'ID не получены',
            'ids.array' => 'ID должны быть массивом',
            'ids.max' => 'Доступно не более 1000 ID',
            'ids.*.required' => 'ID не получен',
            'ids.*.integer' => 'ID должен быть числом',
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        }  

        $data = $request->all();

        $usersToSend = [];

        Users::whereIn('id', $data['ids'])->each(function($user) use(&$usersToSend){
            if($user === null){
                return true;
            }

            $userToSend = [];
            $userToSend['name'] = $user->username;
            $userToSend['picture'] = $user->picture;

            if(AuthController::isUserBanned($user)){
                $userToSend['name'] = 'Аккаунт заморожен';
                $userToSend['picture'] = '/img/removed.webp';
            }

            $usersToSend[$user->id] = $userToSend;
        });

        return $usersToSend;

    }
}
