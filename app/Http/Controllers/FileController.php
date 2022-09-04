<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

use App\Http\Controllers\AuthController;

use App\Models\Files;

class FileController extends Controller
{
    public function uploadImage(Request $request){
        $user = AuthController::isUserAuth($request);
        if($user === false){
            return [
                'action' => 'error',
                'data' => 'Действие запрещенно'
            ];
        }

        $validate = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,bmp,jpg,gif,webp,avif,svg|max:4096|min:1|dimensions:max_width=3840,max_height=2160,min_width=10,min_height=10',
        ],[
            'image.required' => 'Изображение не получено',
            'image.image' => 'Файл должен быть Изображением',
            'image.mines' => 'Неподходящий формат',
            'image.max' => 'Максимальная размер Изображения - 4 мегобайта',
            'image.min' => 'Вес изображения слишком мал',
            'image.dimensions.max_width' => 'Максимальный размер изображения - 8К',
            'image.dimensions.max_height' => 'Максимальный размер изображения - 8К',
            'image.dimensions.min_width' => 'Минимальная ширина изображения - 10 пикселей',
            'image.dimensions.min_height' => 'Минимальная высота изображеня - 10 пикселей',
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        }  

        $token = Str::random(50);
        $filename = $token . '.' . $request->image->extension();

        $file = new Files();
        $file->user_id = $user->id;
        $file->file_token = $token;
        $file->created_at = time();
        $file->file_extension =  $request->image->extension();
        $file->save();

        $request->image->move(public_path('uploaded/images'), $filename);

        return json_encode([
            'success' => 1,
            'file' => [
                'url' => '/uploaded/images/' . $filename
            ]
        ]);

    }
}
