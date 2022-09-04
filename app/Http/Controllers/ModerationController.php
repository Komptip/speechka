<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Comments;
use App\Models\Posts;
use App\Models\Users;
use App\Models\Bans;

use Illuminate\Support\Facades\DB;

use App\Http\Controllers\AuthController;

class ModerationController extends Controller
{
    public function removeComment(Request $request){
        $user = AuthController::isUserAuth($request);
        if($user === false){
            return [
                'action' => 'error',
                'data' => 'Действие запрещенно'
            ];
        }

         $validate = Validator::make($request->all(), [
            'entity_id' => 'required|integer|min:1'
        ],[
            'entity_id.required' => 'ID не получен',
            'entity_id.integer' => 'ID должен быть числом',
            'entity_id.min' => 'ID Некорректнен'
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        }

        if($user['moderator'] == 0){
            return [
                'action' => 'error',
                'data' => 'У вас нет прав на это действие'
            ];
        }

        $comment = Comments::where(['id' => $request->entity_id])->first();

        if($comment === null){
            return [
                'action' => 'error',
                'data' => 'Комментарий не найден'
            ];
        }

        $comment->active = 0;
        $comment->save();

        return [
            'action' => 'success',
            'data' => 'Комментарий успешно удален'
        ];
    }

    public function unremoveComment(Request $request){
        $user = AuthController::isUserAuth($request);
        if($user === false){
            return [
                'action' => 'error',
                'data' => 'Действие запрещенно'
            ];
        }

         $validate = Validator::make($request->all(), [
            'entity_id' => 'required|integer|min:1'
        ],[
            'entity_id.required' => 'ID не получен',
            'entity_id.integer' => 'ID должен быть числом',
            'entity_id.min' => 'ID Некорректнен'
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        }

        if($user['moderator'] == 0){
            return [
                'action' => 'error',
                'data' => 'У вас нет прав на это действие'
            ];
        }

        $comment = Comments::where(['id' => $request->entity_id])->first();

        if($comment === null){
            return [
                'action' => 'error',
                'data' => 'Комментарий не найден'
            ];
        }

        $comment->active = 1;
        $comment->save();

        return [
            'action' => 'success',
            'data' => 'Комментарий успешно восстановлен'
        ];
    }

     public function removePost(Request $request){
        $user = AuthController::isUserAuth($request);
        if($user === false){
            return [
                'action' => 'error',
                'data' => 'Действие запрещенно'
            ];
        }

         $validate = Validator::make($request->all(), [
            'entity_id' => 'required|integer|min:1'
        ],[
            'entity_id.required' => 'ID не получен',
            'entity_id.integer' => 'ID должен быть числом',
            'entity_id.min' => 'ID Некорректнен'
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        }

        if($user['moderator'] == 0){
            return [
                'action' => 'error',
                'data' => 'У вас нет прав на это действие'
            ];
        }

        $post = Posts::where(['id' => $request->entity_id])->first();

        if($post === null){
            return [
                'action' => 'error',
                'data' => 'Пост не найден'
            ];
        }

        $post->active = 0;
        $post->save();

        return [
            'action' => 'success',
            'data' => 'Пост успешно удален'
        ];
    }

    public function unremovePost(Request $request){
        $user = AuthController::isUserAuth($request);
        if($user === false){
            return [
                'action' => 'error',
                'data' => 'Действие запрещенно'
            ];
        }

         $validate = Validator::make($request->all(), [
            'entity_id' => 'required|integer|min:1'
        ],[
            'entity_id.required' => 'ID не получен',
            'entity_id.integer' => 'ID должен быть числом',
            'entity_id.min' => 'ID Некорректнен'
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        }

        if($user['moderator'] == 0){
            return [
                'action' => 'error',
                'data' => 'У вас нет прав на это действие'
            ];
        }

        $post = Posts::where(['id' => $request->entity_id])->first();

        if($post === null){
            return [
                'action' => 'error',
                'data' => 'Пост не найден'
            ];
        }

        $post->active = 1;
        $post->save();

        return [
            'action' => 'success',
            'data' => 'Пост успешно восстановлен'
        ];
    }

    public function banUser(Request $request){
        $user = AuthController::isUserAuth($request);
        if($user === false){
            return [
                'action' => 'error',
                'data' => 'Действие запрещенно'
            ];
        }

         $validate = Validator::make($request->all(), [
            'entity_id' => 'required|integer|min:1'
        ],[
            'entity_id.required' => 'ID не получен',
            'entity_id.integer' => 'ID должен быть числом',
            'entity_id.min' => 'ID Некорректнен'
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        }

        if($user['moderator'] == 0){
            return [
                'action' => 'error',
                'data' => 'У вас нет прав на это действие'
            ];
        }

        $buser = Users::where(['id' => $request->entity_id])->first();

        if($buser === null){
            return [
                'action' => 'error',
                'data' => 'Пост не найден'
            ];
        }

        $newBan = new Bans();
        $newBan->user_id = $buser->id;
        $newBan->moderator_id = $user->id;
        $newBan->save();

        return [
            'action' => 'success',
            'data' => 'Пользователь успешно забанен'
        ];
    }

    public function unbanUser(Request $request){
        $user = AuthController::isUserAuth($request);
        if($user === false){
            return [
                'action' => 'error',
                'data' => 'Действие запрещенно'
            ];
        }

         $validate = Validator::make($request->all(), [
            'entity_id' => 'required|integer|min:1'
        ],[
            'entity_id.required' => 'ID не получен',
            'entity_id.integer' => 'ID должен быть числом',
            'entity_id.min' => 'ID Некорректнен'
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        }

        if($user['moderator'] == 0){
            return [
                'action' => 'error',
                'data' => 'У вас нет прав на это действие'
            ];
        }

        $buser = Users::where(['id' => $request->entity_id])->first();

        if($buser === null){
            return [
                'action' => 'error',
                'data' => 'Пост не найден'
            ];
        }

        $ban = $postRating = DB::select('
                            SELECT * FROM bans WHERE user_id = ? AND (up_to > ? OR up_to IS NULL)
                            ', [$buser->id, time()]);

        if(isset($ban[0])){
            Bans::where(['id' => $ban[0]->id])->delete();
        }

        return [
            'action' => 'success',
            'data' => 'Пост успешно восстановлен'
        ];
    }
}
