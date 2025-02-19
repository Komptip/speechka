<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Models\Posts;
use App\Models\Comments;
use App\Models\PostElements;
use App\Models\ElementData;
use App\Models\Rating;

use App\Models\Communities;
use App\Models\CommunityPosts;
use App\Models\CommunityAdmins;
use App\Models\CommunityBlacklist;

use DOMDocument;

class PostController extends Controller
{

    public function new(Request $request){
        if(AuthController::isUserAuth($request) === false){
            return [
                'action' => 'error',
                'data' => 'Действие запрещенно'
            ];
        }
        return view('newpost');
    }

    public function edit(Request $request, $id){
        $user = AuthController::isUserAuth($request);
        if($user === false){
            return [
                'action' => 'error',
                'data' => 'Действие запрещенно'
            ];
        }

        $post = Posts::where(['id' => $id])->first();

        if($post === null){
            abort(404);
        }

        if($user->id !== $post->user_id){
            return [
                'action' => 'error',
                'data' => 'Это не ваш пост'
            ];
        }


        return view('editpost')->with('post', $post);
    }

    public function view(Request $request, $id){
        $post = Posts::where(['id' => $id])->first();
        if($post === null){
            abort(404);
        }

        return view('viewpost')->with('post', $post);
    }

    public function create(Request $request){
        $user = AuthController::isUserAuth($request);
        if($user === false){
            return [
                'action' => 'error',
                'data' => 'Действие запрещенно'
            ];
        }

        $validate = Validator::make($request->json()->all(), [
            'title' => 'present|string|min:0|max:120',
            'community_id' => 'required'
        ],[
            'title.string' => 'Заголовок должен быть строкой',
            'title.min' => 'Заголовок слишком короткий',
            'title-max' => 'Заголовок слишком длинный',
            'community_id.required' => 'ID подсайта не получен',
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        } 

        $lastPost = Posts::where(['user_id' => $user->id])->orderBy('created_at', 'desc')->first();

        $usrRating = UserController::getRating($user->id);

        if($usrRating < 0){
            if($lastPost !== null){
                $lastPostPublished = time() - $lastPost->created_at;
                $limitForNewPost = abs($usrRating) * (60 * 5);

                if($lastPostPublished <= $limitForNewPost){
                    return [
                        'action' => 'error',
                        'data' => 'Из-за вашего рейтинга вы не можете публиковать новые посты ещё ' . ($limitForNewPost - $lastPostPublished) . ' секунд'
                    ];
                }
            }
        }

        $data = $request->json()->all();

        $validateResult = self::validatePostData($data);

        if($validateResult !== true){
            return $validateResult;
        }

        if($data['community_id'] === false){
            $community = false;
        } else {
             $validate = Validator::make(['community_id' => $data['community_id']], [
                'community_id' => 'integer|min:1',
            ],[
                'community_id.integer' => 'ID подсайта должен быть числом',
                'community_id.min' => 'ID некорректен',
            ]);

             if($validate->fails()){
                return [
                    'action' => 'error',
                    'data' => $validate->errors()->first()
                ];
            }

            $community = Communities::where(['id' => $data['community_id'], 'active' => 1])->first();

            if($community === null){
                return [
                    'action' => 'error',
                    'data' => 'Подсайт не найден'
                ];
            }

            if($community->mode == 1){
                if(CommunityAdmins::where(['community_id' => $community->id, 'user_id' => $user->id])->first() === null){
                    return [
                        'action' => 'error',
                        'data' => 'В этом подсайте разрешено писать только администраторам'
                    ];       
                }
            }

            if(CommunityBlacklist::where(['user_id' => $user->id, 'community_id' => $community->id])->first() != null){
                return [
                    'action' => 'error',
                    'data' => 'Вы в черном списке этого подсайта'
                ];
            }
        }

        $newPost = new Posts();
        $newPost->user_id = $user->id;
        $newPost->created_at = time();
        $newPost->title = $data['title'];
        $newPost->active = 1;
        $newPost->save();

        if($community){
            $toCommunity = new CommunityPosts();
            $toCommunity->post_id = $newPost->id;
            $toCommunity->community_id = $community->id;
            $toCommunity->save();
        }

        foreach($request->json()->all()['data'] as $element){
            $newElement = new PostElements();
            $newElement->post_id = $newPost->id;
            $newElement->type = $element['type'];
            $newElement->save();

            foreach ($element['data'] as $key => $value) {
                if($key == 'file'){
                    $newData = new ElementData();
                    $newData->element_id = $newElement->id;
                    $newData->key = $key;
                    $newData->value = is_array($value) ? $value['url'] : '';
                    $newData->save();
                } else
                if($key == 'items'){
                    foreach ($value as $item) {
                        $newData = new ElementData();
                        $newData->element_id = $newElement->id;
                        $newData->key = 'list_item';
                        $newData->value = $item;
                        $newData->save();
                    }
                } else {
                    $newData = new ElementData();
                    $newData->element_id = $newElement->id;
                    $newData->key = $key;
                    $newData->value = $value;
                    $newData->save();
                }
            }
        }

        $response = new Response(json_encode(['action' => 'redirect', 'data' => '/p/' . $newPost->id]));

        return $response;
    }

    public function save(Request $request){
        $user = AuthController::isUserAuth($request);
        if($user === false){
            return [
                'action' => 'error',
                'data' => 'Действие запрещенно'
            ];
        }

        $validate = Validator::make($request->json()->all(), [
            'title' => 'present|string|min:0|max:120',
            'id' => 'required|integer|min:1',
        ],[
            'title.string' => 'Заголовок должен быть строкой',
            'title.min' => 'Заголовок слишком короткий',
            'title-max' => 'Заголовок слишком длинный',
            'id.required' => 'ID не получен',
            'id.integer' => 'ID должен быть числом',
            'id.min' => 'ID некорректен'
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        } 

        $data = $request->json()->all();

        $post = Posts::where(['id' => $data['id']])->first();

        $post->title = $data['title'];
        $post->edited = true;
        $post->save();

        if($post === null){
            return [
                'action' => 'error',
                'data' => 'Пост не найден'
            ];
        }

        
        $validateResult = self::validatePostData($data);

        if($validateResult !== true){
            return $validateResult;
        }

        PostElements::where(['post_id' => $post->id])->each(function($oldElement){
            ElementData::where(['element_id' => $oldElement->id])->delete();
            $oldElement->delete();
        });

        foreach($request->json()->all()['data'] as $element){
            $newElement = new PostElements();
            $newElement->post_id = $post->id;
            $newElement->type = $element['type'];
            $newElement->save();

            foreach ($element['data'] as $key => $value) {
                if($key == 'file'){
                    $newData = new ElementData();
                    $newData->element_id = $newElement->id;
                    $newData->key = $key;
                    $newData->value = is_array($value) ? $value['url'] : '';
                    $newData->save();
                } else
                if($key == 'items'){
                    foreach ($value as $item) {
                        $newData = new ElementData();
                        $newData->element_id = $newElement->id;
                        $newData->key = 'list_item';
                        $newData->value = $item;
                        $newData->save();
                    }
                } else {
                    $newData = new ElementData();
                    $newData->element_id = $newElement->id;
                    $newData->key = $key;
                    $newData->value = $value;
                    $newData->save();
                }
            }
        }

        $response = new Response(json_encode(['action' => 'success', 'data' => 'Пост сохранен']));

        return $response;
    }

    public function validatePostData($data){
        $allowedPostTags = [
            'image' => [
                'caption',
                'file',
                'stretched',
                'withBackground',
                'withBorder',
            ],
            'paragraph' => [
                'text'
            ],
            'header' => [
                'level',
                'text'
            ],
            'list' => [
                'items',
                'style'
            ],
            'integration' => [
                'url'
            ]
        ]; 

        $allowedTextTags = [
            'a' => [
                'href'
            ],
            'b' => [

            ],
            'i' => [

            ],
            'br' => [

            ],
            '#text' => [

            ]
        ];

        if(!isset($data['data'])){
            return [
                'action' => 'error',
                'data' => 'Пост не получен'
            ];
        }

        if(!is_array($data['data'])){
            return [
                'action' => 'error',
                'data' => 'Ошибка в структуре поста'
            ];  
        }

        if(count($data['data']) > 50){
            return [
                'action' => 'error',
                'data' => 'Пост слишком длинный, не более 50 элементов'
            ];
        }

        foreach($data['data'] as $element){
            if(!isset($element['type'])){
                return [
                    'action' => 'error',
                    'data' => 'Ошибка в структуре элемента'
                ];         
            }

            if(!isset($allowedPostTags[$element['type']])){
                return [
                    'action' => 'error',
                    'data' => 'Используется запрещенный тэг'
                ];
            }

            if(!isset($element['data'])){
                return [
                    'action' => 'error',
                    'data' => 'Не получена информация о элементе'
                ];
            }

            if(!is_array($element['data'])){
                return [
                    'action' => 'error',
                    'data' => 'Не получена информация о элементе'
                ];
            }

            foreach ($element['data'] as $key => $value) {

                if(!in_array($key, $allowedPostTags[$element['type']])){
                    return [
                        'action' => 'error',
                        'data' => 'Используется запрещенный аттрибут элемента'
                    ];
                }

                if(!is_array($value)){
                    if(strlen($value) > 5000){
                        return [
                            'action' => 'error',
                            'data' => 'Текст и/или атрибут одного из элементов привышает 5000 символов'
                        ];
                    }

                    if(strlen($value) > 0){
                        $doc = new DOMDocument();
                        @$doc->loadHTML("test" . $value);    

                        $result = $doc->getElementsByTagName('*');

                        foreach($doc->childNodes[1]->childNodes[0]->childNodes[0]->childNodes as $node) {
                            if(!array_key_exists($node->nodeName, $allowedTextTags)){
                                return [
                                    'action' => 'error',
                                    'data' => 'Текст и/или атрибут одного из элементов содержит запрещенные тэги'
                                ];           
                            }     

                            if($node->childNodes->length > 0){
                                foreach ($node->childNodes as $chldNode) {
                                    if($chldNode->nodeName !== '#text'){
                                        return [
                                            'action' => 'error',
                                            'data' => 'Текст и/или атрибут одного из элементво содержит запрещенные под-тэги'
                                        ];  
                                    }
                                }

                                if($chldNode->childNodes->length > 0){
                                    return [
                                        'action' => 'error',
                                        'data' => 'Запрещенный уроверь вложенности'
                                    ];  
                                }
                            }                     

                            if ($node->hasAttributes()) {
                                foreach ($node->attributes as $attr) {
                                    if(!in_array($attr->nodeName, $allowedTextTags[$node->nodeName])){
                                        return [
                                            'action' => 'error',
                                            'data' => 'Используются запрещенные атрибуты-атрибута элемента'
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }

                else {

                    if(count($value) > 100){
                        return [
                            'action' => 'error',
                            'data' => 'Колечство под-атрибутов одного из элементов привышает допустимое количество'
                        ];
                    }

                    foreach ($value as $subvalue) {
                        if(is_array($subvalue)){
                            return [
                                'action' => 'error',
                                'data' => 'Запрещенный уроверь вложенности'
                            ];
                        }

                        if(strlen($subvalue) > 5000){
                            return [
                                'action' => 'error',
                                'data' => 'Текст и/или атрибут одного из элементов привышает 5000 символов'
                            ];
                        }

                        if(strlen($subvalue) > 0){
                            $doc = new DOMDocument();
                            $doc->loadHTML("test" . $subvalue);    

                            $result = $doc->getElementsByTagName('*');

                            foreach($doc->childNodes[1]->childNodes[0]->childNodes[0]->childNodes as $node) {
                                if(!array_key_exists($node->nodeName, $allowedTextTags)){
                                    return [
                                        'action' => 'error',
                                        'data' => 'Текст и/или атрибут одного из элементов содержит запрещенные тэги'
                                    ];           
                                }     

                                if($node->childNodes->length > 0){
                                    foreach ($node->childNodes as $chldNode) {
                                        if($chldNode->nodeName !== '#text'){
                                            return [
                                                'action' => 'error',
                                                'data' => 'Текст и/или атрибут одного из элементво содержит запрещенные под-тэги'
                                            ];  
                                        }
                                    }

                                    if($chldNode->childNodes->length > 0){
                                        return [
                                            'action' => 'error',
                                            'data' => 'Запрещенный уроверь вложенности'
                                        ];  
                                    }
                                }                     

                                if ($node->hasAttributes()) {
                                    foreach ($node->attributes as $attr) {
                                        if(!in_array($attr->nodeName, $allowedTextTags[$node->nodeName])){
                                            return [
                                                'action' => 'error',
                                                'data' => 'Используются запрещенные атрибуты-атрибута элемента'
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                    }

                }

            }
        }

        return true;
    }

    public function delete(Request $request){
        $user = AuthController::isUserAuth($request);

        if($user === false){
            return [
                'action' => 'error',
                'data' => 'Действие запрещенно'
            ];
        }

        $validate = Validator::make($request->all(), [
            'post_id' => 'required|integer|min:1'
        ],[
            'post_id.required' => 'ID поста не получен',
            'post_id.integer' => 'IO должен быть числом',
            'post_id.min' => "Некорректный ID"
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        } 

        $data = $request->all();

        $post = Posts::Where(['id' => $data['post_id']])->first();

        if($post === null){
            return [
                'action' => 'error',
                'data' => 'Пост не найден'
            ];
        }

        if($post->author_id !== $user->id){

            $postCommunity = CommunityPosts::where(['post_id' => $post->id])->first();

            if($postCommunity === null){
                return [
                    'action' => 'error',
                    'data' => 'Пост не найден'
                ];
            }

            if(CommunityAdmins::where(['community_id' => $postCommunity->community_id, 'user_id' => $user->id])->first() === null){
                return [
                    'action' => 'error',
                    'data' => 'Пост не найден'
                ];
            }
        
        }

        $post->active = 0;
        $post->save();

        return [
            'action' => 'success',
            'data' => 'Пост успешно удален'
        ];
    }

    public function recreate(Request $request){
        $user = AuthController::isUserAuth($request);

        if($user === false){
            return [
                'action' => 'error',
                'data' => 'Действие запрещенно'
            ];
        }

        $validate = Validator::make($request->all(), [
            'post_id' => 'required|integer|min:1'
        ],[
            'post_id.required' => 'ID поста не получен',
            'post_id.integer' => 'IO должен быть числом',
            'post_id.min' => "Некорректный ID"
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        } 

        $data = $request->all();

        $post = Posts::Where(['id' => $data['post_id']])->first();

        if($post === null){
            return [
                'action' => 'error',
                'data' => 'Пост не найден'
            ];
        }

        if($post->author_id !== $user->id){

            $postCommunity = CommunityPosts::where(['post_id' => $post->id])->first();

            if($postCommunity === null){
                return [
                    'action' => 'error',
                    'data' => 'Пост не найден'
                ];
            }

            if(CommunityAdmins::where(['community_id' => $postCommunity->id, 'user_id' => $user->id])->first() === null){
                return [
                    'action' => 'error',
                    'data' => 'Пост не найден'
                ];
            }
        
        }

        $post->active = 1;
        $post->save();

        return [
            'action' => 'success',
            'data' => 'Пост успешно восстановлен'
        ];
    }

    public function getPost(Request $request){
        $user = AuthController::isUserAuth($request);

        $validate = Validator::make($request->all(), [
            'ids' => 'required|array|max:10',
            'ids.*' => 'required|integer',
            'full' => 'integer|min:0|max:1'
        ],[
            'ids.required' => 'ID не получены',
            'ids.array' => 'ID должны быть массивом',
            'ids.max' => 'Доступно не более 10 ID',
            'ids.*.required' => 'ID не получен',
            'ids.*.integer' => 'ID должен быть числом',
            'full.integer' => 'Full должен быть числом',
            'full.min' => 'Full не должен быть меньше 0',
            'full.max' => 'Full не должен быть больше 1'
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        }  

        $postsToSend = [];

        $data = $request->all();

        $query = Posts::whereIn('id', $data['ids']);

        foreach ($data['ids'] as $id) { 
            $query->orderByRaw("id = {$id} desc");
        }

        $query->each(function($post) use(&$postsToSend, &$user, &$data){

            if($post === null){
                return true;
            }

            $postToSend = [];
            $postToSend['author_id'] = $post->user_id;
            $elements = [];
            if($post->active){
                $postToSend['title'] = $post->title;

                foreach (PostElements::where(['post_id' => $post->id])->limit(isset($data['full']) ? -1 : 2)->get() as $element) {
                     $elementToSend = [
                        'type' => $element->type
                    ];
                    $data = [];
                    ElementData::where(['element_id' => $element->id])->each(function($edata) use(&$data){
                        $data[$edata->key] = $edata->value;
                    });

                    if($element->type == 'list'){
                        unset($data['list_item']);
                        $data['list_items'] = ElementData::where(['element_id' => $element->id, 'key' => 'list_item'])->pluck('value')->toArray();
                    }

                    $elementToSend['data'] = $data;

                    array_push($elements, $elementToSend);
                }
            } else {
                $postToSend['title'] = 'Пост удален';
            }

            $postToSend['elements'] = $elements;

            $postToSend['grade'] = null;

            if($user !== false){
                $rating = Rating::where(['user_id' => $user->id, 
                    'type' => 0, 
                    'entity_id' => $post->id])->first();
                if($rating !== null){
                    $postToSend['grade'] = $rating->value == 1;
                }
            }

            $postToSend['id'] = $post->id;

            if($postToSend['title'] !== null){
                if(strlen(preg_replace('/\s+/', '', $postToSend['title'])) < 1){
                    $postToSend['title'] = null;
                }
            }

            $postToSend['comments_count'] = Comments::where(['post_id' => $post->id])->count();

            $postRating = [];

            Rating::where(['type' => 0, 'entity_id' => $post->id])->select(array('user_id', 'value'))->each(function($rating) use(&$postRating){
                $postRating[$rating->user_id] = $rating->value;
            });

            $postToSend['rating'] = $postRating;

            $postToSend['created_at'] = $post->created_at;

            $postToSend['active'] = $post->active;

            $postToSend['edited'] = $post->edited;

            $postCommunity = CommunityPosts::where(['post_id' => $post->id])->first();

            if($postCommunity === null){
                $postToSend['community_id'] = false;
            } else {
                $postToSend['community_id'] = $postCommunity->community_id;
            }

            array_push($postsToSend, $postToSend);

        });

        return $postsToSend;
    }

    public function getPostForEdit(Request $request){
        $user = AuthController::isUserAuth($request);

        if($user == false){
            return [
                'action' => 'error',
                'data' => 'Действие запрещенно'
            ];
        }

        $validate = Validator::make($request->all(), [
            'id' => 'required|integer|min:1'
        ],[
            'id.required' => 'ID не получен',
            'id.integer' => 'ID должен быть числом',
            'id.min' => 'ID Некорректен'
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        } 

        $data = $request->all();

        $post = Posts::where(['id' => $request['id']])->first();

        if($post === null){
            return [
                'action' => 'error',
                'data' => 'Пост не найден'
            ];
        } 

        if($post->user_id !== $user->id){
            return [
                'action' => 'error',
                'data' => 'Это не ваш пост'
            ];
        }

        $postToSend = [];
        $elements = [];
        $postToSend['title'] = $post->title;

        foreach (PostElements::where(['post_id' => $post->id])->get() as $element) {
             $elementToSend = [
                'type' => $element->type
            ];
            $data = [];
            ElementData::where(['element_id' => $element->id])->each(function($edata) use(&$data){
                $data[$edata->key] = $edata->value;
            });

            if($element->type == 'list'){
                unset($data['list_item']);
                $data['items'] = ElementData::where(['element_id' => $element->id, 'key' => 'list_item'])->pluck('value')->toArray();
            }

            if($element->type == 'image'){
                $data['file'] = [
                    'url' => $data['file']
                ];
            }

            $elementToSend['data'] = $data;

            array_push($elements, $elementToSend);
        }

        $postToSend['data'] = $elements;

        return $postToSend;
    }

    public function getPostsByUser(Request $request){
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
            return Posts::where('id', '<', min($data['black-list']))->latest()->where(['user_id' => $data['user_id']])->where('active', 1)->take(5)->pluck('id')->toArray();
        } else {
            return Posts::latest()->where(['user_id' => $data['user_id']])->where('active', 1)->take(5)->pluck('id')->toArray();
        }
    }

    public function getPostsByCommunity(Request $request){
        $validate = Validator::make($request->all(), [
            'community_id' => 'required|integer',
            'black-list' => 'array|max:500',
            'black-list.*' => 'required|integer|min:1',
        ],[
            'community_id.required' => 'ID не получен',
            'community_id.integer' => 'ID должен быть числом',
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
            return Posts::leftJoin('community_posts', function($join) use($data){
                $join->on('community_posts.post_id', '=', 'posts.id')->where('community_posts.community_id' , '=', $data['community_id']);
            })->whereNotNull('community_posts.id')->latest()->where('posts.id', '<', min($data['black-list']))->where('posts.active', 1)->take(5)->pluck('posts.id')->toArray();
        } else {
            return Posts::leftJoin('community_posts', function($join) use($data){
                $join->on('community_posts.post_id', '=', 'posts.id')->where('community_posts.community_id' , '=', $data['community_id']);
            })->whereNotNull('community_posts.id')->latest()->where('active', 1)->take(5)->pluck('posts.id')->toArray();
        }
    }

    public function newest(Request $request){
        $validate = Validator::make($request->all(), [
            'black-list' => 'array|max:500',
            'black-list.*' => 'required|integer|min:1',
        ],[
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
            return Posts::where([['id', '<', min($data['black-list'])], ['active', 1]])->latest()->limit(10)->pluck('id')->toArray();
        } else {
            return Posts::latest()->where('active', 1)->take(10)->pluck('id')->toArray();
        }
    }

    public function popular(Request $request){
        $validate = Validator::make($request->all(), [
            'black-list' => 'array|max:500',
            'black-list.*' => 'required|integer|min:1',
        ],[
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
            $posts = DB::select('
                SELECT posts.id, 
                (
                    (? - posts.created_at) / 
                    SUM(IF(ratings.value=0, -1, IF(ratings.value=1, 1, 0)))
                ) AS ratio, SUM(IF(ratings.value=0, -1, IF(ratings.value=1, 1, 0))) as rating
                FROM posts 
                LEFT JOIN ratings 
                    ON posts.id = ratings.entity_id  AND ratings.type = 0
                WHERE posts.id NOT IN (' . implode(',', $data['black-list']) . ') AND posts.active = 1
                GROUP BY posts.id
                ORDER BY case 
                    when  (SUM(IF(ratings.value=0, -1, IF(ratings.value=1, 1, 0))) < 0)
                    then 3
                    when (((?  - posts.created_at) / SUM(IF(ratings.value=0, -1, IF(ratings.value=1, 1, 0)))) is null) 
                    then 2
                    when SUM(IF(ratings.value=0, -1, IF(ratings.value=1, 1, 0))) < 5 then 1 else 0 end, 
                    ((?  - posts.created_at) / SUM(IF(ratings.value=0, -1, IF(ratings.value=1, 1, 0))))
                LIMIT ?;
                ', [time(), time(), time(), 10]);
        } else {
            $posts = DB::select('
                SELECT posts.id, 
                (
                    (? - posts.created_at) / 
                    SUM(IF(ratings.value=0, -1, IF(ratings.value=1, 1, 0)))
                ) AS ratio, SUM(IF(ratings.value=0, -1, IF(ratings.value=1, 1, 0))) as rating
                FROM posts 
                LEFT JOIN ratings 
                    ON posts.id = ratings.entity_id AND ratings.type = 0
                WHERE posts.active = 1
                GROUP BY posts.id
                ORDER BY case 
                    when  (SUM(IF(ratings.value=0, -1, IF(ratings.value=1, 1, 0))) < 0)
                    then 3
                    when (((?  - posts.created_at) / SUM(IF(ratings.value=0, -1, IF(ratings.value=1, 1, 0)))) is null) 
                    then 2
                    when SUM(IF(ratings.value=0, -1, IF(ratings.value=1, 1, 0))) < 5 then 1 else 0 end, 
                    ((?  - posts.created_at) / SUM(IF(ratings.value=0, -1, IF(ratings.value=1, 1, 0))))
                LIMIT ?;
                ', [time(), time(), time(), 10]);
        }



        $postsToSend = [];

        foreach ($posts as $post) {
            array_push($postsToSend, $post->id);
        }

        return $postsToSend;
    }

    public function getTitle(Request $request){
        $validate = Validator::make($request->all(), [
            'ids' => 'required|array|max:10',
            'ids.*' => 'required|integer'
        ],[
            'ids.required' => 'ID не получены',
            'ids.array' => 'ID должны быть массивом',
            'ids.max' => 'Доступно не более 10 ID',
            'ids.*.required' => 'ID не получен',
            'ids.*.integer' => 'ID должен быть числом'
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        }  

        $postsToSend = [];

        $data = $request->all();

        $query = Posts::whereIn('id', $data['ids']);

        foreach ($data['ids'] as $id) { 
            $query->orderByRaw("id = {$id} desc");
        }

        $query->each(function($post) use(&$postsToSend){

            if($post === null){
                return true;
            }

            $postsToSend[$post->id] = $post->title;

        });

        return $postsToSend;
    }

    public function setRating(Request $request){
        
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

        $post = Posts::where(['id' => $data['entity_id']])->first();

        if($post === null){
            return [
                'action' => 'error',
                'data' => 'Сущность не найдена'
            ];   
        }

        if($post->user_id == $user->id){
            return [
                'action' => 'error',
                'data' => 'Нельзя лайкать самого себя'
            ];   
        }

        Rating::where(['type' => 0, 'user_id' => $user->id, 'entity_id' => $data['entity_id']])->delete();

        if($data['type'] !== 'null'){
            $newRating = new Rating();
            $newRating->user_id = $user->id;
            $newRating->value = $_POST['type'] == 'true' ? 1 : 0;
            $newRating->type = 0;
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
        $post = Posts::where(['id' => $data['entity_id']])->first();

        $postRating = [];

        Rating::where(['type' => 0, 'entity_id' => $post->id])->select(array('user_id', 'value'))->each(function($rating) use(&$postRating){
            $postRating[$rating->user_id] = $rating->value;
        });

        return $postRating;

    }
}
