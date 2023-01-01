<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

use Illuminate\Support\Facades\DB;

use App\Models\Users;
use App\Models\Bans;
use App\Models\PasswordResets;
use App\Models\RegistrationConfirmations;
use App\Models\UserAuthTokens;

use App\Mail\PasswordResetMail;
use App\Mail\RegistrationConfirmMail;

use App\Http\Controllers\UserController;

class AuthController extends Controller
{
    public function SignUp(Request $request){
        $validatorRules = [
            'name' => 'required|unique:users,username|max:30',
            'email' => 'required|email|unique:users,email|max:50',
            'password' => 'required|min:6|max:30'
        ];

        if(self::useRecaptcha()) {
            if(!self::verifyRecaptcha($request->all()['g-recaptcha-response'])){
                return [
                    'action' => 'error',
                    'data' => 'Капча не пройдена'
                ];
            }
            $validatorRules['g-recaptcha-response'] = 'required';
        }

        $validate = Validator::make($request->all(), $validatorRules,[
            'name.required' => 'Имя объязательно',
            'name.unique' => 'Пользователь с таким именем уже существует',
            'name.max' => 'Длинна пароля не должна привышать 30 символов',
            'email.required' => 'Почта объязательна',
            'email.email' => 'Почта в неправильном формате',
            'email.unique' => 'Почта уже используется',
            'password.required' => 'Пароль объязателен',
            'password.min' => 'Длинна пароля должна быть как минимум 6 символов',
            'password.max' => 'Длинна пароля должна быть не более 30 символов',
            'g-recaptcha-response.required' => 'Капча объязательна'
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        }

        $data = $request->all();


        $newUser = new Users();
        $newUser->username = $data['name'];
        $newUser->email = $data['email'];
        $newUser->password = password_hash($data['password'], PASSWORD_DEFAULT);
        $newUser->moderator = 0;
        $newUser->confirmed = 0;
        $newUser->created_at = time();
        $newUser->save();

        $confirmToken = Str::random(50);

        $newUserConfirm = new RegistrationConfirmations();
        $newUserConfirm->user_id = $newUser->id;
        $newUserConfirm->token = $confirmToken;
        $newUserConfirm->save();

        Mail::to($data['email'])->send(new RegistrationConfirmMail($confirmToken));

        return [
            'action' => 'success',
            'data' => 'Успешно! Письмо с подтверждением регистрации было отправлено вам на почту. (ПИСЬМО МОЖЕТ ПОПАСТЬ В СПАМ)'
        ];
    }

    public function logIn(Request $request){
        $validatorRules = [
            'email' => 'required|email|max:50',
            'password' => 'required|max:30'
        ];

        if(self::useRecaptcha()) {
            if(!self::verifyRecaptcha($request->all()['g-recaptcha-response'])){
                return [
                    'action' => 'error',
                    'data' => 'Капча не пройдена'
                ];
            }
            $validatorRules['g-recaptcha-response'] = 'required';
        }

        $validate = Validator::make($request->all(), $validatorRules,[
            'email.required' => 'Почта обязательна',
            'email.email' => 'Почта в неправильном формате',
            'email.max' => 'Почта не должна привышать 50 символов',
            'password.required' => 'Пароль обязателен',
            'password.max' => 'Пароль не должен привышать 30 символов',
            'g-recaptcha-response.required' => 'Капча обязательна'
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        }

        $data = $request->all();

        $user = Users::where(['email' => $data['email'], 'confirmed' => 1])->first();

        if($user === null){
            return [
                'action' => 'error',
                'data' => 'Имя пользователя или пароль не верны'
            ];
        }

        if(!password_verify($data['password'], $user->password)){
            return [
                'action' => 'error',
                'data' => 'Имя пользователя или пароль не верны'
            ];
        }

        $authToken = Str::random(250);

        $newAuthToken = new UserAuthTokens();
        $newAuthToken->token = $authToken;
        $newAuthToken->user_id = $user->id;
        $newAuthToken->save();

        $response = new Response(json_encode(['action' => 'reload-user-data']));
        $response->withCookie(cookie('auth', $authToken, 60 * 24 * 365));

        return $response;
    }

    public function passwordReset(Request $request){
        $validatorRules = [
            'email' => 'required|email|max:50'
        ];

        if(self::useRecaptcha()) {
            if(!self::verifyRecaptcha($request->all()['g-recaptcha-response'])){
                return [
                    'action' => 'error',
                    'data' => 'Капча не пройдена'
                ];
            }
            $validatorRules['g-recaptcha-response'] = 'required';
        }

        $validate = Validator::make($request->all(), $validatorRules,[
            'email.required' => 'Почта объязательна',
            'email.email' => 'Почта в неправильном формате',
            'email.max' => 'Почта не должна привышать 50 символов',
            'g-recaptcha-response.required' => 'Капча объязательна'
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        }

        $data = $request->all();

        $user = Users::where(['email' => $data['email'], 'confirmed' => 1])->first();

        if($user === null){
            return [
                'action' => 'error',
                'data' => 'Аккаунта с такой почтой нет'
            ];
        }


        $oldPasswordReset = PasswordResets::where(['user_id' => $user->id])->first();

        if($oldPasswordReset !== null){

            if((time() - $oldPasswordReset->created_at) <= 600){
                return [
                    'action' => 'error',
                    'data' => 'Предыдущая попытка восстановления пароля была меньше 10 минут назад'
                ];
            }

        }

        PasswordResets::where(['user_id' => $user->id])->delete();

        $resetToken = Str::random(50);

        $AddNewPasswordReset = new PasswordResets();
        $AddNewPasswordReset->user_id = $user->id;
        $AddNewPasswordReset->token = $resetToken;
        $AddNewPasswordReset->created_at = time();
        $AddNewPasswordReset->save();

        Mail::to($user->email)->send(new PasswordResetMail($resetToken));

        return [
            'action' => 'success',
            'data' => 'Письмо с инструкцией по сбросу пароля было отправлено вам на почту'
        ];
    }

    public function get(Request $request){
        $authToken = $request->cookie('auth');
        if($authToken === null){
            return [
                'user' => false
            ];
        }

        $auth = UserAuthTokens::where(['token' => $authToken])->first();

        if($auth == null){
            return ['user' => false];
        }

        $user = Users::where(['id' => $auth->user_id])->first();

        $banned = self::isUserBanned($user);

        return [
            'user' => [
                'id' => $user['id'],
                'name' => $banned ? 'Аккаунт заморожен' : $user['username'],
                'picture' => $banned ? '/img/removed.webp' : $user['picture'],
                'moderator' => $user['moderator'] == 1
            ]
        ];
    }

    public function logOut(Request $request){
        $authToken = $request->cookie('auth');
        if($authToken !== null){
            $auth = UserAuthTokens::where(['token' => $authToken])->first();

            if($auth !== null){
                $auth->delete();
            }
        }

        $response = new Response(json_encode(['action' => 'reload-user-data']));
        $response->withCookie(cookie('auth', '', 0));

        return $response;
    }

    public static function isUserAuth(Request $request){
        $authToken = $request->cookie('auth');
        if($authToken === null){
            return false;
        }

        $auth = UserAuthTokens::where(['token' => $authToken])->first();

        if($auth == null){
            return false;
        }

        $user = Users::where(['id' => $auth->user_id])->first();

        if(self::isUserBanned($user)){
            return false;
        }

        return $user;
    }

    public function isUserBanned($user){
        $ban = $postRating = DB::select('
                            SELECT * FROM bans WHERE user_id = ? AND (up_to > ? OR up_to IS NULL)
                            ', [$user->id, time()]);

        if(isset($ban[0])){
            return true;
        }

        if(UserController::getRating($user->id) < -15){
            return true;
        }

        return false;
    }

    public static function useRecaptcha(){
        if (!App::environment('local')) {
            return true;
        }
        return false;
    }

    public static function verifyRecaptcha($key){
        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
            'form_params' => [
                'secret' =>  config('speechka.recaptcha_secret'),
                'response' => $key,
                'remoteip' => $_SERVER['REMOTE_ADDR']
            ],
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        ]);

        try {
            return json_decode($response->getBody())->success === true;
        } catch(Exception $e) {
            return false;
        }
    }

    public function newPassword($token){
        $passwordReset = PasswordResets::where(['token' => $token])->first();
        if($passwordReset === null){
            abort(404);
        }

        return view('newpassword')->with('token', $token);
    }

    public function confirmRegistration($token){
        $registrationConfirm = RegistrationConfirmations::where(['token' => $token])->first();
        if($registrationConfirm === null){
            abort(404);
        }

        $authToken = Str::random(250);

        $user = Users::where(['id' => $registrationConfirm->user_id, 'confirmed' => 0])->first();
        if ($user === null) {
            abort(404);
        }
        $user->confirmed = 1;
        $user->save();

        $newAuthToken = new UserAuthTokens();
        $newAuthToken->token = $authToken;
        $newAuthToken->user_id = $user->id;
        $newAuthToken->save();

        RegistrationConfirmations::where(['id' => $registrationConfirm->id])->delete();

        return redirect('/')->withCookie(cookie('auth', $authToken, 60 * 24 * 365));
    }

    public function setNewPassword(Request $request){
        $validate = Validator::make($request->all(), [
            'token' => 'required',
            'password' => 'required|min:6|max:30'
        ],[
            'token.required' => 'Токен объязателен',
            'password.required' => 'Пароль объязательн',
            'password.min' => 'Пароль слишком короткий',
            'password.max' => 'Пароль слишком длинный'
        ]);

        if($validate->fails()){
            return [
                'action' => 'error',
                'data' => $validate->errors()->first()
            ];
        }

        $data = $request->all();

        $passwordReset = PasswordResets::where(['token' => $data['token']])->first();
        if($passwordReset === null){
            abort(404);
        }

        $user = Users::where(['id' => $passwordReset->user_id])->first();

        PasswordResets::where(['token' => $data['token']])->delete();

        $authToken = Str::random(250);

        $user->password = password_hash($data['password'], PASSWORD_DEFAULT);
        $user->save();

        $newAuthToken = new UserAuthTokens();
        $newAuthToken->token = $authToken;
        $newAuthToken->user_id = $user->id;
        $newAuthToken->save();

        $response = new Response(json_encode(['action' => 'redirect', 'data' => '/']));
        $response->withCookie(cookie('auth', $authToken, 60 * 24 * 365));

        return $response;
    }
}
