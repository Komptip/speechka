<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

use Intervention\Image\ImageManagerStatic as Image;

use App\Models\Communities;
use App\Models\CommunityAdmins;

class CommunityController extends Controller
{
    public function create(Request $request){
        $user = AuthController::isUserAuth($request);
        if($user === false){
            return [
                'action' => 'error',
                'data' => 'Действие запрещенно'
            ];
        }

        $validate = Validator::make($request->all(), [
            'photo' => 'image|mimes:jpeg,png,bmp,jpg,gif,webp,avif,svg|max:10240|min:1|dimensions:max_width=7680,max_height=4320,min_width=10,min_height=10',
            'name' => 'min:1|unique:communities,name|max:50',
            'description' => 'max:250'
        ],[
            'photo.image' => 'Файл должен быть Изображением',
            'photo.mines' => 'Неподходящий формат',
            'photo.max' => 'Максимальная размер Изображения - 10 мегобайт',
            'name.unique' => 'Подсайт с таким названием уже существует', 
            'name.min' => 'Название не может быть пустым', 
            'name.max' => 'Название слишком длинное',
            'name.max' => 'Название слишком длинное',
            'description.max' => 'Описание слишком длинное' 
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        }

        if(UserController::getRating($user->id) < 1){
            return [
                'action' => 'error',
                'data' => 'Что-бы создавать новые подсайты ваш рейтинг должен быть больше нуля'
            ];
        }

        $data = $request->all();

        $newCommunity = new Communities();
        $newCommunity->name = $data['name'];
        $newCommunity->description = $data['description'];
        $newCommunity->active = 1;
        $newCommunity->mode = 0;

        if(isset($request->photo)){

            $token = Str::random(50);
            $filename = $token . '.' . $request->photo->extension();

            $request->photo->move(public_path('uploaded/images'), $filename);

            $newCommunity->picture = '/uploaded/images/' . $filename;

        }

        $newCommunity->save();

        $newAdmin = new CommunityAdmins();
        $newAdmin->community_id = $newCommunity->id;
        $newAdmin->user_id = $user->id;
        $newAdmin->save();

        $response = new Response(json_encode(['action' => 'redirect', 'data' => '/c/' . $newCommunity->id]));

        return $response;
    }

    public function getForSidebar(Request $request){
        return Communities::where(['active' => 1])->get()->pluck('id')->toArray();
    }

    public function all(Request $request){
        return Communities::where(['active' => 1])->get()->pluck('id')->toArray();
    }

    public function getCommunity(Request $request){
        $user = AuthController::isUserAuth($request);

        $validate = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'required|integer'
        ],[
            'ids.required' => 'ID не получены',
            'ids.array' => 'ID должны быть массивом',
            'ids.*.required' => 'ID не получен',
            'ids.*.integer' => 'ID должен быть числом'
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        }  

        $communitiesToSend = [];

        $data = $request->all();

        $query = Communities::whereIn('id', $data['ids']);

        foreach ($data['ids'] as $id) { 
            $query->orderByRaw("id = {$id} desc");
        }

        $query->each(function($community) use(&$communitiesToSend){
            
            if($community->active == 1){
                $communitiesToSend[$community->id] = [
                    'name' => $community->name,
                    'picture' => $community->picture,
                    'description' => $community->description,
                    'mode' => $community->mode,
                    'active' => 1
                ];
            } else {
                $communitiesToSend[$community->id] = [
                    'name' => 'Подсайт удален',
                    'picture' => '',
                    'description' => '',
                    'mode' => 1,
                    'active' => 0
                ];
            }

        });

        return $communitiesToSend;
    }

    public function view(Request $request, $id){
        $community = Communities::where(['id' => $id])->first();

        if($community === null){
            abort(404);
        }

        return view('community')->with('id', $id);
    }

    public function getAdminedCommunities(Request $request){
        $user = AuthController::isUserAuth($request);
        if($user === false){
            return [];
        }

        return CommunityAdmins::where(['user_id' => $user->id])->pluck('community_id')->toArray();
    }
}
