<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
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

    function getRating($id){
        $postRating = DB::select('
            SELECT users.id, SUM(IF(ratings.value=0, -1, IF(ratings.value=1, 1, 0))) AS rating
            FROM users LEFT JOIN posts
                ON users.id = posts.user_id
            LEFT JOIN ratings
                ON posts.id = ratings.entity_id AND ratings.type = 0
            WHERE users.id = ?
            GROUP BY users.id
            ORDER BY rating
            ', [$id])[0]->rating;

        $commentRating = DB::select('
            SELECT users.id, SUM(IF(ratings.value=0, -1, IF(ratings.value=1, 1, 0))) AS rating
            FROM users LEFT JOIN comments
                ON users.id = comments.user_id
            LEFT JOIN ratings
                ON comments.id = ratings.entity_id AND ratings.type = 1
            WHERE users.id = ?
            GROUP BY users.id
            ORDER BY rating
            ', [$id])[0]->rating;

        return $postRating + $commentRating;
    }
}
