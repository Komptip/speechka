<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

use App\Models\Posts;
use App\Models\Comments;
use App\Models\Files;
use App\Models\Rating;
use App\Models\CommentAttachments;

use App\Http\Controllers\OrlovController;

class CommentController extends Controller
{
    function create(Request $request){
        $user = AuthController::isUserAuth($request);
        if($user === false){
            return [
                'action' => 'error',
                'data' => 'Действие запрещенно'
            ];
        }

        $validate = Validator::make($request->all(), [
            'text' => 'present|string|max:1000|nullable',
            'post-id' => 'required|integer',
            'reply-id' => 'integer|min:1'
        ],[
            'text.max' => 'Текст комментария не должен привышать 1000 символов',
            'post-id.required' => 'ID Поста не получен',
            'post-id.integer' => 'ID Поста должен быть числом',
            'reply-id.integer' => 'Reply ID должен быть числом',
            'reply-id.min' => 'Reply ID Некорректнен'
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        } 

        $data = $request->all();

        $post = Posts::where(['id' => $data['post-id']])->first();

        if($post === null){
            return [
                'action' => 'error',
                'data' => 'Пост не найден'
            ];
        }

        $orlovTriggered = OrlovController::detectTrigger($data['text']);

        if(isset($data['reply-id'])){
            $commentToReply = Comments::where(['id' => $data['reply-id'], 'post_id' => $post->id])->first();

            if($commentToReply === null){
                return [
                    'action' => 'error',
                    'data' => 'Оригинальный комментарий не найден'
                ];
            }

            if($commentToReply->user_id === OrlovController::$account_id){
                $orlovTriggered = true;
            }

            if($this->getLevelOfReply($commentToReply) >= 8){
                $commentToReply = Comments::where(['id' => $commentToReply->reply_to])->first();
            }
        }

        if($request->attachment == 'undefined'){
            if(strlen(preg_replace('/\s+/', '', $request->text)) < 1){
                return [
                    'action' => 'error',
                    'data' => 'Комментарий не может быть пустым'
                ];
            }
        } else {
            $validate = Validator::make($request->all(), [
                'attachment' => 'present|image|mimes:jpeg,png,bmp,jpg,gif,webp,avif,svg|max:10240|min:1|dimensions:max_width=7680,max_height=4320,min_width=10,min_height=10|nullable',
            ],[
                'attachment.image' => 'Приложение должно быть изображением',
                'attachment.mines' => 'Запрещенный формат приложения',
                'attachment.max' => 'Приложение должно быть не больше 4 мегобайт',
                'attachment.min' => 'Приложение не должно быть пустым',
            ]);

            if($validate->fails()){
                return [
                    'action' => 'error',
                    'data' => $validate->errors()->first()
                ];
            } 
        }

        $newComment = new Comments();
        $newComment->content = $request->text;
        $newComment->user_id = $user->id;
        $newComment->post_id = $post->id;
        $newComment->created_at = time();
        $newComment->active = 1;

        if(isset($data['reply-id'])){
            $newComment->reply_to = $commentToReply->id;
        }

        $newComment->save();

        if($orlovTriggered){
            $orlovResponse = new Comments();
            $orlovResponse->content = OrlovController::getResponse();
            $orlovResponse->post_id = $post->id;
            $orlovResponse->user_id = OrlovController::$account_id;
            $orlovResponse->created_at = time();
            $orlovResponse->active = 1;

            if($this->getLevelOfReply($newComment) >= 8){
                $orlovResponse->reply_to = Comments::where(['id' => $newComment->reply_to])->first()->id;
            } else {
                $orlovResponse->reply_to = $newComment->id;
            }

            $orlovResponse->save();
        }

        if($request->attachment != 'undefined'){

            $token = Str::random(50);
            $filename = $token . '.' . $request->attachment->extension();

            $file = new Files();
            $file->user_id = $user->id;
            $file->file_token = $token;
            $file->created_at = time();
            $file->file_extension =  $request->attachment->extension();
            $file->save();

            $request->attachment->move(public_path('uploaded/images'), $filename);

            $newAttachment = new CommentAttachments();
            $newAttachment->comment_id = $newComment->id;
            $newAttachment->file_id = $file->id;
            $newAttachment->save();
        }

        return [
            'action' => 'success',
            'data' => 'Комментарий успешно опубликован'
        ];
    }

    public function getCommentsByUser(Request $request){
        $validate = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'black-list' => 'array|max:500',
            'black-list.*' => 'required|integer|min:1',
        ],[
            'user_id.required' => 'ID не получен',
            'user_id.integer' => 'ID должен быть числом',
            'black-list.array' => 'Черный список должны быть массивом',
            'black-list.max' => 'Черный список не должен привышать 500',
            'black-list.max' => 'Доступно не более 10 ID',
            'black-list.*.required' => 'ID не получен',
            'black-list.*.integer' => 'ID должен быть числом',
            'black-list.*.min' => 'ID не может быть меньше 1'
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        }  

        $data = $request->all();

        if(isset($data['black-list'])){
            return Comments::where('id', '<', min($data['black-list']))->latest()->where(['user_id' => $data['user_id']])->take(5)->pluck('id')->toArray();
        } else {
            return Comments::latest()->where(['user_id' => $data['user_id']])->take(5)->pluck('id')->toArray();
        }
    }

    function getLevelOfReply($replyComment){
        $replyLevel = 1;
        while($replyComment->reply_to !== null){

            $replyComment = Comments::where(['id' => $replyComment->reply_to])->first();

            $replyLevel++;

        }

        return $replyLevel;
    }

    function getCommentsByPost(Request $request){
        $validate = Validator::make($request->all(), [
            'post-id' => 'required|integer|min:1'
        ],[
            'post-id.required' => 'ID не получен',
            'post-id.integer' => 'ID должен быть числом',
            'post-id.min' => 'Некорректный IP'
        ]);

        $data = $request->all();

        $post = Posts::where(['id' => $data['post-id']])->first();

        if($post === null){
            return [
                'action' => 'error',
                'data' => 'Пост не найден'
            ];
        }

        return Comments::where(['post_id' => $post->id])->get()->pluck('id')->toArray();
    }

    function newest(Request $request){

        return Comments::where(['active' => 1])->latest()->take(10)->get()->sortByDesc('id')->pluck('id')->toArray();
    }

    function getComment(Request $request){
        $user = AuthController::isUserAuth($request);

        $validate = Validator::make($request->all(), [
            'ids' => 'required|array|max:1000',
            'ids.*' => 'required|integer'
        ],[
            'ids.required' => 'ID не получены',
            'ids.array' => 'ID должны быть массивом',
            'ids.max' => 'Доступно не более 1000 ID',
            'ids.*.required' => 'ID не получен',
            'ids.*.integer' => 'ID должен быть числом'
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        }  

        $data = $request->all();

        $commentsToSend = [];

        $query = Comments::whereIn('id', $data['ids']);

        foreach ($data['ids'] as $id) { 
            $query->orderByRaw("id = {$id} desc");
        }

        $query->each(function($comment) use(&$commentsToSend, &$user){

            $commentToSend = [];

            $commentToSend['id'] = $comment->id;
            $commentToSend['author_id'] = $comment->user_id;
            $commentToSend['created_at'] = $comment->created_at;
            $commentToSend['content'] = $comment->content;
            $commentToSend['reply_to'] = $comment->reply_to;
            $commentToSend['post_id'] = $comment->post_id;
            $commentToSend['active'] = $comment->active;
            $commentToSend['sub_comments'] = [
                'ids' => Comments::where(['reply_to' => $comment->id])->get()->pluck('id')->toArray()
            ];

            $commentToSend['grade'] = null;

            if($user !== false){
                $rating = Rating::where(['user_id' => $user->id, 
                    'type' => 1, 
                    'entity_id' => $comment->id])->first();
                if($rating !== null){
                    $commentToSend['grade'] = $rating->value == 1;
                }
            }

            $commentRating = [];

            Rating::where(['type' => 1, 'entity_id' => $comment->id])->select(array('user_id', 'value'))->each(function($rating) use(&$commentRating){
                $commentRating[$rating->user_id] = $rating->value;
            });

            $commentToSend['rating'] = $commentRating;

            if(count($commentToSend['sub_comments']['ids']) < 1){
                $commentToSend['sub_comments'] = false;
            }

            $attachment = CommentAttachments::where(['comment_id' => $comment->id])->first();
            if($attachment == null){
                $commentToSend['attachment'] = false;
            } else {
                $file = Files::where(['id' => $attachment->file_id])->first();
                $commentToSend['attachment'] = '/uploaded/images/' . $file->file_token . '.' . $file->file_extension;
            }

            if($comment->active == 0){
                $commentToSend['content'] = 'Комментарий удален';
            }

            array_push($commentsToSend, $commentToSend);

        });

        return $commentsToSend;

    }

    function setRating(Request $request){
        $user = AuthController::isUserAuth($request);
        if($user === false){
            return [
                'action' => 'error',
                'data' => 'Действие запрещенно'
            ];
        }

        $validate = Validator::make($request->all(), [
            'entity_id' => 'required|integer',
            'type' => 'required'
        ],[
            'entity_id.required' => 'ID сущности не получен',
            'untity_id.integer' => 'ID должен быть числом',
            'type.required' => 'Тип не получен'
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        }

        $data = $request->all();

        if(!in_array($data['type'], array('true', 'false', 'null'))){
            return [
                'action' => 'error',
                'data' => 'Некоректный тип'
            ];   
        }

        $comment = Comments::where(['id' => $data['entity_id']])->first();

        if($comment === null){
            return [
                'action' => 'error',
                'data' => 'Сущность не найдена'
            ];   
        }

        if($user->id == $comment->user_id){
            return [
                'action' => 'error',
                'data' => 'Нельзя лайкать самого себя'
            ];   
        }

        Rating::where(['type' => 1, 'user_id' => $user->id, 'entity_id' => $data['entity_id']])->delete();

        if($data['type'] !== 'null'){
            $newRating = new Rating();
            $newRating->user_id = $user->id;
            $newRating->value = $_POST['type'] == 'true' ? 1 : 0;
            $newRating->type = 1;
            $newRating->entity_id = $data['entity_id'];
            $newRating->save();
        }
    }

    public function getRating(Request $request){

        $validate = Validator::make($request->all(), [
            'entity_id' => 'required|integer'
        ],[
            'entity_id.required' => 'ID сущности не получен',
            'untity_id.integer' => 'ID должен быть числом'
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        }
        
        $data = $request->all();
        $post = Comments::where(['id' => $data['entity_id']])->first();

        $commentRating = [];

        Rating::where(['type' => 1, 'entity_id' => $post->id])->select(array('user_id', 'value'))->each(function($rating) use(&$commentRating){
            $commentRating[$rating->user_id] = $rating->value;
        });

        return $commentRating;

    }
}
