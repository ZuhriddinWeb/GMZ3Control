<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\UnitsController;
use App\Http\Controllers\api\GraphicTimesController;
use App\Http\Controllers\api\SourcesController;
use App\Http\Controllers\api\ChangesController;
use App\Http\Controllers\api\GraphicsController;
use App\Http\Controllers\api\ParamTypesController;
use App\Http\Controllers\api\ParamsController;
use App\Http\Controllers\api\FactoryController;
use App\Http\Controllers\api\FactoryStructureController;
use App\Http\Controllers\api\ParametrValueController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\api\ParamsGraphController;
use App\Http\Controllers\api\BlogsController;
use App\Http\Controllers\TreeController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::post('/login' , [UserController::class, 'login']);
Route::post('/logout' , [UserController::class, 'login']);

Route::middleware('auth:sanctum')->get('/user', [UserController::class, 'authenticatedUser']);

Route::match(['get', 'post', 'put', 'delete'], '/units/{id?}', [UnitsController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/graphictimes/{id?}', [GraphicTimesController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/sources/{id?}', [SourcesController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/changes/{id?}', [ChangesController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/graphics/{id?}', [GraphicsController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/paramtypes/{id?}', [ParamTypesController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/param/{id?}', [ParamsController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/source/{id?}', [SourcesController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/factory/{id?}', [FactoryController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/structure/{id?}', [FactoryStructureController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/vparams/{id?}', [ParametrValueController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/users/{id?}', [UserController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/role/{id?}', [RoleController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/user_role/{id?}', [UserRoleController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/paramsgraph/{id?}', [ParamsGraphController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/blogs/{id?}', [BlogsController::class, 'handle']);
Route::get('/tree', [TreeController::class, 'getTree']);
Route::post('/node-clicked', [TreeController::class, 'handleNodeClick']);
Route::post('/treeChart', [TreeController::class, 'treeChart']);

Route::get('get-params-for-user-count/{user_id}/{change_id}' , [ParamsGraphController::class, 'getParamsForUserCount']);
Route::get('get-params-for-id-edit/{param_id}' , [ParamsGraphController::class, 'getRowParamEdit']);
Route::get('get-params-for-user/{user_id}/{change_id}' , [ParamsGraphController::class, 'getParamsForUser']);
Route::get('get-params-for-id/{param_id}' , [ParametrValueController::class, 'getParamsForId']);

