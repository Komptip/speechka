<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;

class CaptchaController extends Controller
{
    public function getKey() {
        $captcha_key = App::environment('local') ? '' : config('speechka.recaptcha_key');
        $response = new Response(json_encode(['key' => $captcha_key]));

        return $response;
    }
}
