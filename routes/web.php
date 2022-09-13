<?php

use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('popular');
});

Route::get('/new', function () {
    return view('new');
});

Route::get('/popular', function () {
    return view('popular');
});

Route::get('/post/new', 'App\Http\Controllers\PostController@new');
Route::get('/post/edit/{id}', 'App\Http\Controllers\PostController@edit');

Route::get('/p/{id}', 'App\Http\Controllers\PostController@view');

Route::get('/u/{id}/{type?}', 'App\Http\Controllers\UserController@profile');

Route::get('/c/{id}', 'App\Http\Controllers\CommunityController@view');

Route::get('/password-reset/{token}', 'App\Http\Controllers\AuthController@newPassword');
Route::get('/registration-confirm/{token}', 'App\Http\Controllers\AuthController@confirmRegistration');

Route::post('/data/auth/signup', 'App\Http\Controllers\AuthController@SignUp');
Route::post('/data/auth/login', 'App\Http\Controllers\AuthController@logIn');
Route::post('/data/auth/password-reset', 'App\Http\Controllers\AuthController@passwordReset');
Route::post('/data/auth/get', 'App\Http\Controllers\AuthController@get');
Route::post('/data/auth/logout', 'App\Http\Controllers\AuthController@logOut');
Route::post('/data/auth/new-password-set', 'App\Http\Controllers\AuthController@setNewPassword');

Route::post('/data/post/create', 'App\Http\Controllers\PostController@create');
Route::post('/data/post/get', 'App\Http\Controllers\PostController@getPost');
Route::post('/data/post/get-for-edit', 'App\Http\Controllers\PostController@getPostForEdit');
Route::post('/data/post/edit', 'App\Http\Controllers\PostController@save');

Route::post('/data/post/get-titles-by-ids', 'App\Http\Controllers\PostController@getTitle');

Route::post('/data/post/delete', 'App\Http\Controllers\PostController@delete');
Route::post('/data/post/recreate', 'App\Http\Controllers\PostController@recreate');

Route::post('/data/comment/create', 'App\Http\Controllers\CommentController@create');
Route::post('/data/comment/get-by-post', 'App\Http\Controllers\CommentController@getCommentsByPost');
Route::post('/data/comment/newest', 'App\Http\Controllers\CommentController@newest');
Route::post('/data/comment/get', 'App\Http\Controllers\CommentController@getComment');

Route::post('/data/post/newest', 'App\Http\Controllers\PostController@newest');
Route::post('/data/post/popular', 'App\Http\Controllers\PostController@popular');

Route::post('/data/community/get-for-sidebar', 'App\Http\Controllers\CommunityController@getForSidebar');
Route::post('/data/community/get', 'App\Http\Controllers\CommunityController@getCommunity');
Route::post('/data/community/create', 'App\Http\Controllers\CommunityController@create');
Route::post('/data/community/all', 'App\Http\Controllers\CommunityController@all');
Route::post('/data/community/get-admined', 'App\Http\Controllers\CommunityController@getAdminedCommunities');

Route::post('/data/post/rating/set', 'App\Http\Controllers\PostController@setRating');
Route::post('/data/post/rating/get', 'App\Http\Controllers\PostController@getRating');

Route::post('/data/comment/rating/set', 'App\Http\Controllers\CommentController@setRating');
Route::post('/data/comment/rating/get', 'App\Http\Controllers\CommentController@getRating');

Route::post('/data/comment/remove', 'App\Http\Controllers\ModerationController@removeComment');
Route::post('/data/comment/unremove', 'App\Http\Controllers\ModerationController@unremoveComment');

Route::post('/data/post/remove', 'App\Http\Controllers\ModerationController@removePost');
Route::post('/data/post/unremove', 'App\Http\Controllers\ModerationController@unremovePost');

Route::post('/data/user/ban', 'App\Http\Controllers\ModerationController@banUser');
Route::post('/data/user/unban', 'App\Http\Controllers\ModerationController@unbanUser');

Route::post('/data/community/ban', 'App\Http\Controllers\ModerationController@banCommunity');
Route::post('/data/community/unban', 'App\Http\Controllers\ModerationController@unbanCommunity');

Route::post('/data/users/get', 'App\Http\Controllers\UserController@getByID');

Route::post('/data/settings/user/set', 'App\Http\Controllers\SettingController@setUserSettings');
Route::post('/data/settings/community/set', 'App\Http\Controllers\SettingController@setCommunitySettings');
Route::post('/data/settings/community/get', 'App\Http\Controllers\SettingController@getCommunitySettings');
Route::post('/data/settings/community/get-admins', 'App\Http\Controllers\SettingController@getCommunityAdmins');
Route::post('/data/settings/community/get-blacklist', 'App\Http\Controllers\SettingController@getCommunityBlacklist');
Route::post('/data/settings/community/add-admin', 'App\Http\Controllers\SettingController@AddAdminToCommunity');
Route::post('/data/settings/community/remove-admin', 'App\Http\Controllers\SettingController@RemoveAdminFromCommunity');
Route::post('/data/settings/community/add-to-blacklist', 'App\Http\Controllers\SettingController@addUserToCommunityBlacklist');
Route::post('/data/settings/community/remove-from-blacklist', 'App\Http\Controllers\SettingController@RemoveUserFromCommunitBlacklisty');

Route::post('/data/post/get-by-user', 'App\Http\Controllers\PostController@getPostsByUser');
Route::post('/data/post/get-by-community', 'App\Http\Controllers\PostController@getPostsByCommunity');
Route::post('/data/comment/get-by-user', 'App\Http\Controllers\CommentController@getCommentsByUser');

Route::post('/files/upload-image', 'App\Http\Controllers\FileController@uploadImage');
