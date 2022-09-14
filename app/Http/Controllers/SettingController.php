<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

use Intervention\Image\ImageManagerStatic as Image;

use App\Http\Controllers\AuthController;

use App\Models\Files;
use App\Models\Users;
use App\Models\Communities;
use App\Models\CommunityAdmins;
use App\Models\CommunityBlacklist;

class SettingController extends Controller
{
    function setUserSettings(Request $request){
        $user = AuthController::isUserAuth($request);
        if($user === false){
            return [
                'action' => 'error',
                'data' => 'Действие запрещенно'
            ];
        }

        $validate = Validator::make($request->all(), [
            'photo' => 'image|mimes:jpeg,png,bmp,jpg,gif,webp,avif,svg|max:4096|min:1|dimensions:max_width=3840,max_height=2160,min_width=10,min_height=10',
            'new_password' => 'min:6|max:30',
            'old_password' => 'min:6|max:30'
        ],[
            'photo.required' => 'Изображение не получено',
            'photo.image' => 'Файл должен быть Изображением',
            'photo.mines' => 'Неподходящий формат',
            'photo.max' => 'Максимальная размер Изображения - 4 мегобайта',
            'new_password.string' => 'Новый пароль должен быть строкой',
            'new_password.min' => 'Новый пароль слишком короткий', 
            'new_password.max' => 'Новый пароль слишком длинный',
            'old_password.string' => 'Новый пароль должен быть строкой',
            'old_password.min' => 'Старый пароль слишком короткий', 
            'old_password.max' => 'Старый пароль слишком длинный' 
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        }

        if(isset($request->new_password) && isset($request->old_password)){
            if(!password_verify($request->old_password, $user->password)){
                return [
                    'action' => 'error',
                    'data' => 'Неправильный пароль'
                ];
            }

            if(password_verify($request->new_password, $user->password)){
                return [
                    'action' => 'error',
                    'data' => 'Пароли одинаковые'
                ];
            }

            $user->password = password_hash($request->new_password, PASSWORD_DEFAULT);
            $user->save();
        }  

        if(isset($request->photo)){

            $token = Str::random(50);
            $filename = $token . '.' . $request->photo->extension();

            if($user->picture !== null){
                if(file_exists(public_path($user->picture))){
                    unlink(public_path($user->picture));
                }
            }

            $request->photo->move(public_path('uploaded/images'), $filename);

            $user->picture = '/uploaded/images/' . $filename;
            $user->save();

        }


        return [
            'action' => 'success',
            'data' => 'Настройки успешно сохранены'
        ];
    }

    function setCommunitySettings(Request $request){
        $user = AuthController::isUserAuth($request);
        if($user === false){
            return [
                'action' => 'error',
                'data' => 'Действие запрещенно'
            ];
        }

        $validate = Validator::make($request->all(), [
            'community_id' => 'required|integer|min:1',
            'name' => 'required|min:1|max:50',
            'description' => 'required|max:250',
            'new_picture' => 'required'
        ],[
            'community_id.required' => 'ID подсайта не получен',
            'community_id.integer' => 'ID подсайта должен быть числом',
            'community_id.min' => 'ID подсайта некорректен',
            'name.required' => 'Имя подсайта не получено',
            'name.max' => 'Имя подсайта слишком длинное',
            'description.required' => 'Описание не получено',
            'description.max' => 'Описание слишком длинное'
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        }

        $data = $request->all();

        $community = Communities::where(['id' => $data['community_id']])->first();

        if($community === null){
            return [
                'action' => 'error',
                'data' => 'Подсайт не найден'
            ];
        }

        $admin = CommunityAdmins::where(['user_id' => $user->id, 'community_id' => $community->id])->first();

        if($admin === null){
            return [
                'action' => 'error',
                'data' => 'Вы не администратор этого подсайта'
            ];
        }

        if($community->name !== $data['name']){
            $isdUplicate = Communities::where(['name' => $data['name']])->first() === null;
            if(!$isdUplicate){
                return [
                    'action' => 'error',
                    'data' => 'Уже существует другое сообщество с таким именем'
                ];
            }

            $community->name = $data['name'];
        }

        $community->description = $data['description'];
        $community->save();

        if($data['new_picture'] == 'true'){

            $validate = Validator::make($request->all(), [
                'picture' => 'required|image|mimes:jpeg,png,bmp,jpg,gif,webp,avif,svg|max:10240|min:1|dimensions:max_width=7680,max_height=4320,min_width=10,min_height=10'
            ],[
                'picture.required' => 'Изображение не получено',
                'picture.image' => 'Файл должен быть Изображением',
                'picture.mines' => 'Неподходящий формат',
                'picture.max' => 'Максимальная размер Изображения - 10 мегобайтов'
            ]);

            if($validate->fails()){
                return [
                    'action' => 'error',
                    'data' => $validate->errors()->first()
                ];
            }

            $token = Str::random(50);
            $filename = $token . '.' . $request->picture->extension();

            if($community->picture !== null){
                if(file_exists(public_path($community->picture))){
                    unlink(public_path($community->picture));
                }
            }

            $request->picture->move(public_path('uploaded/images'), $filename);

            $community->picture = '/uploaded/images/' . $filename;
            $community->save();

        }

        return [
            'action' => 'success',
            'data' => 'Настройки успешно сохранены'
        ];
    }

    function getCommunitySettings(Request $request){
        $user = AuthController::isUserAuth($request);
        if($user === false){
            return [
                'action' => 'error',
                'data' => 'Действие запрещенно'
            ];
        }

        $validate = Validator::make($request->all(), [
            'community_id' => 'required|integer|min:1'
        ],[
            'community_id.required' => 'ID подсайта не получен',
            'community_id.integer' => 'ID подсайта должен быть числом',
            'community_id.min' => 'ID подсайта некорректен'
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        }

        $data = $request->all();

        $community = Communities::where(['id' => $data['community_id']])->first();

        if($community === null){
            return [
                'action' => 'error',
                'data' => 'Подсайт не найден'
            ];
        }

        $admin = CommunityAdmins::where(['user_id' => $user->id, 'community_id' => $community->id])->first();

        if($admin === null){
            return [
                'action' => 'error',
                'data' => 'Вы не администратор этого подсайта'
            ];
        }

        return [
            'name' => $community->name,
            'description' => $community->description,
            'picture' => $community->picture
        ];
    }

    function getCommunityAdmins(Request $request){
        $user = AuthController::isUserAuth($request);
        if($user === false){
            return [
                'action' => 'error',
                'data' => 'Действие запрещенно'
            ];
        }

        $validate = Validator::make($request->all(), [
            'community_id' => 'required|integer|min:1'
        ],[
            'community_id.required' => 'ID подсайта не получен',
            'community_id.integer' => 'ID подсайта должен быть числом',
            'community_id.min' => 'ID подсайта некорректен'
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        }

        $data = $request->all();

        $community = Communities::where(['id' => $data['community_id']])->first();

        if($community === null){
            return [
                'action' => 'error',
                'data' => 'Подсайт не найден'
            ];
        }

        $admin = CommunityAdmins::where(['user_id' => $user->id, 'community_id' => $community->id])->first();

        if($admin === null){
            return [
                'action' => 'error',
                'data' => 'Вы не администратор этого подсайта'
            ];
        }

        return CommunityAdmins::where(['community_id' => $community->id])->get()->pluck('user_id')->toArray();
    }

    function getCommunityBlacklist(Request $request){
        $user = AuthController::isUserAuth($request);
        if($user === false){
            return [
                'action' => 'error',
                'data' => 'Действие запрещенно'
            ];
        }

        $validate = Validator::make($request->all(), [
            'community_id' => 'required|integer|min:1'
        ],[
            'community_id.required' => 'ID подсайта не получен',
            'community_id.integer' => 'ID подсайта должен быть числом',
            'community_id.min' => 'ID подсайта некорректен'
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        }

        $data = $request->all();

        $community = Communities::where(['id' => $data['community_id']])->first();

        if($community === null){
            return [
                'action' => 'error',
                'data' => 'Подсайт не найден'
            ];
        }

        $admin = CommunityAdmins::where(['user_id' => $user->id, 'community_id' => $community->id])->first();

        if($admin === null){
            return [
                'action' => 'error',
                'data' => 'Вы не администратор этого подсайта'
            ];
        }

        return CommunityBlacklist::where(['community_id' => $community->id])->get()->pluck('user_id')->toArray();
    }

    function AddAdminToCommunity(Request $request){
        $user = AuthController::isUserAuth($request);
        if($user === false){
            return [
                'action' => 'error',
                'data' => 'Действие запрещенно'
            ];
        }

        $validate = Validator::make($request->all(), [
            'community_id' => 'required|integer|min:1',
            'user' => 'required|string'
        ],[
            'community_id.required' => 'ID подсайта не получен',
            'community_id.integer' => 'ID подсайта должен быть числом',
            'community_id.min' => 'ID подсайта некорректен',
            'user.required' => 'Пользователь не получен',
            'user.string' => 'Пользователь некорректен'
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        }

        $data = $request->all();

        $community = Communities::where(['id' => $data['community_id']])->first();

        if($community === null){
            return [
                'action' => 'error',
                'data' => 'Подсайт не найден'
            ];
        }

        $admin = CommunityAdmins::where(['user_id' => $user->id, 'community_id' => $community->id])->first();

        if($admin === null){
            return [
                'action' => 'error',
                'data' => 'Вы не администратор этого подсайта'
            ];
        }

        $userToAdd = Users::where(['id' => $data['user']])->first();

        if($userToAdd === null){

            $userToAdd = Users::where(['username' => $data['user']])->first();

            if($userToAdd === null){

                return [
                    'action' => 'error',
                    'data' => 'Пользователь не найден'
                ];

            }

        }

        if(CommunityAdmins::where(['user_id' => $userToAdd->id, 'community_id' => $community->id])->first() !== null){
            return [
                'action' => 'error',
                'data' => 'Пользователь уже является администратором подсайта'
            ];
        }

        $newAdmin = new CommunityAdmins();
        $newAdmin->user_id = $userToAdd->id;
        $newAdmin->community_id = $community->id;
        $newAdmin->save();

        return [
            'action' => 'success',
            'data' => 'Администратор подсайта добавлен'
        ];  
    }

    function RemoveAdminFromCommunity(Request $request){
        $user = AuthController::isUserAuth($request);
        if($user === false){
            return [
                'action' => 'error',
                'data' => 'Действие запрещенно'
            ];
        }

        $validate = Validator::make($request->all(), [
            'community_id' => 'required|integer|min:1',
            'user_id' => 'required|min:1'
        ],[
            'community_id.required' => 'ID подсайта не получен',
            'community_id.integer' => 'ID подсайта должен быть числом',
            'community_id.min' => 'ID подсайта некорректен',
            'user_id.required' => 'ID пользователя не получен',
            'user_id.min' => 'ID пользователя некорректен'
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        }

        $data = $request->all();

        $community = Communities::where(['id' => $data['community_id']])->first();

        if($community === null){
            return [
                'action' => 'error',
                'data' => 'Подсайт не найден'
            ];
        }

        $admin = CommunityAdmins::where(['user_id' => $user->id, 'community_id' => $community->id])->first();

        if($admin === null){
            return [
                'action' => 'error',
                'data' => 'Вы не администратор этого подсайта'
            ];
        }

        $userToAdd = Users::where(['id' => $data['user_id']])->first();

        if($userToAdd === null){

            return [
                'action' => 'error',
                'data' => 'Пользователь не найден'
            ];


        }

        $communityAdmin = CommunityAdmins::where(['user_id' => $userToAdd->id, 'community_id' => $community->id])->first();

        if($communityAdmin === null){
            return [
                'action' => 'error',
                'data' => 'Пользователь и так не является администратором подсайта'
            ];
        }

        $communityAdmin->delete();

        return [
            'action' => 'success',
            'data' => 'Администратор подсайта удален'
        ];  
    }

    function addUserToCommunityBlacklist(Request $request){
        $user = AuthController::isUserAuth($request);
        if($user === false){
            return [
                'action' => 'error',
                'data' => 'Действие запрещенно'
            ];
        }

        $validate = Validator::make($request->all(), [
            'community_id' => 'required|integer|min:1',
            'user' => 'required|string'
        ],[
            'community_id.required' => 'ID подсайта не получен',
            'community_id.integer' => 'ID подсайта должен быть числом',
            'community_id.min' => 'ID подсайта некорректен',
            'user.required' => 'Пользователь не получен',
            'user.string' => 'Пользователь некорректен'
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        }

        $data = $request->all();

        $community = Communities::where(['id' => $data['community_id']])->first();

        if($community === null){
            return [
                'action' => 'error',
                'data' => 'Подсайт не найден'
            ];
        }

        $admin = CommunityAdmins::where(['user_id' => $user->id, 'community_id' => $community->id])->first();

        if($admin === null){
            return [
                'action' => 'error',
                'data' => 'Вы не администратор этого подсайта'
            ];
        }

        $userToAdd = Users::where(['id' => $data['user']])->first();

        if($userToAdd === null){

            $userToAdd = Users::where(['username' => $data['user']])->first();

            if($userToAdd === null){

                return [
                    'action' => 'error',
                    'data' => 'Пользователь не найден'
                ];

            }

        }

        if(CommunityBlacklist::where(['user_id' => $userToAdd->id, 'community_id' => $community->id])->first() !== null){
            return [
                'action' => 'error',
                'data' => 'Пользователь уже в черном списке'
            ];
        }

        $toBlacklist = new CommunityBlacklist();
        $toBlacklist->user_id = $userToAdd->id;
        $toBlacklist->community_id = $community->id;
        $toBlacklist->save();

        return [
            'action' => 'success',
            'data' => 'Пользователь добавлен в черный список'
        ];  
    }

    function RemoveUserFromCommunitBlacklisty(Request $request){
        $user = AuthController::isUserAuth($request);
        if($user === false){
            return [
                'action' => 'error',
                'data' => 'Действие запрещенно'
            ];
        }

        $validate = Validator::make($request->all(), [
            'community_id' => 'required|integer|min:1',
            'user_id' => 'required|min:1'
        ],[
            'community_id.required' => 'ID подсайта не получен',
            'community_id.integer' => 'ID подсайта должен быть числом',
            'community_id.min' => 'ID подсайта некорректен',
            'user_id.required' => 'ID пользователя не получен',
            'user_id.min' => 'ID пользователя некорректен'
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        }

        $data = $request->all();

        $community = Communities::where(['id' => $data['community_id']])->first();

        if($community === null){
            return [
                'action' => 'error',
                'data' => 'Подсайт не найден'
            ];
        }

        $admin = CommunityAdmins::where(['user_id' => $user->id, 'community_id' => $community->id])->first();

        if($admin === null){
            return [
                'action' => 'error',
                'data' => 'Вы не администратор этого подсайта'
            ];
        }

        $userToAdd = Users::where(['id' => $data['user_id']])->first();

        if($userToAdd === null){

            return [
                'action' => 'error',
                'data' => 'Пользователь не найден'
            ];


        }

        $usrInBlacklist = CommunityBlacklist::where(['user_id' => $userToAdd->id, 'community_id' => $community->id])->first();

        if($usrInBlacklist === null){
            return [
                'action' => 'error',
                'data' => 'Пользователь и так не находмтся в черном списке'
            ];
        }

        $usrInBlacklist->delete();

        return [
            'action' => 'success',
            'data' => 'Пользователь удален из черного списка'
        ];  
    }
}
