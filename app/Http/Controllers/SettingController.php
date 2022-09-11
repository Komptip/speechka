<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

use Intervention\Image\ImageManagerStatic as Image;

use App\Http\Controllers\AuthController;

use App\Models\Files;

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

            $image_resize = Image::make($request->photo->getRealPath());
            $image_resize->save(public_path('uploaded/images/' . $filename));

            $user->picture = '/uploaded/images/' . $filename;
            $user->save();

        }


        return [
            'action' => 'success',
            'data' => 'Настройки успешно сохранены'
        ];
    }
}
