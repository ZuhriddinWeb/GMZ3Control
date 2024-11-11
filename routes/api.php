<?php

use App\Http\Controllers\api\CalculatorController;
use App\Http\Controllers\api\FormulaController;
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
use App\Http\Controllers\api\NumberPageController;

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
Route::match(['get', 'post', 'put', 'delete'], '/pages/{id?}', [NumberPageController::class, 'handle']);

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
Route::match(['get', 'post', 'put', 'delete'], '/formula/{id?}', [FormulaController::class, 'handle']);
Route::match(['get', 'post', 'put', 'delete'], '/calculator/{id?}', [CalculatorController::class, 'handle']);

Route::get('/tree', [TreeController::class, 'getTree']);
Route::post('/node-clicked', [TreeController::class, 'handleNodeClick']);
Route::post('/treeChart', [TreeController::class, 'treeChart']);

Route::get('get-params-for-user-count/{user_id}/{change_id}' , [ParamsGraphController::class, 'getParamsForUserCount']);
Route::get('get-params-for-id-edit/{param_id}' , [ParamsGraphController::class, 'getRowParamEdit']);
Route::get('get-params-for-user/{user_id}/{change_id}/{date}/{tabId}' , [ParamsGraphController::class, 'getParamsForUser']);
Route::get('get-params-for-id/{param_id}' , [ParametrValueController::class, 'getParamsForId']);
Route::post('vparamsEdit' , [ParametrValueController::class, 'update']);
Route::get('vparams-value/{factoryId}/{cuurent_date}' , [ParametrValueController::class, 'getByBlog']);

Route::get('restart-password/{user_id}' , [UserController::class, 'restart']);
Route::get('/broadcast-time', [ParametrValueController::class, 'sendTimeUpdate']);
Route::get('/vparamsGetValue/{id}', [ParametrValueController::class, 'vparamsGetValue']);

// Route::get('/vparamsuser/{blog_id}/{change_id}/{date}', [ParametrValueController::class, 'getByBlog']);
Route::get('/paramWithId/{id}',  [ParamsGraphController::class, 'getRowParamID']);
Route::get('/pages-select/{id}',  [NumberPageController::class, 'getRowPages']);
Route::get('/getRowPage/{id}',  [NumberPageController::class, 'getRowPage']);

Route::get('/get-graph-with-params/{id}',  [ParamsGraphController::class, 'getGraficWithParams']);
Route::get('/getForFormule/{id}',  [ParamsGraphController::class, 'getForFormule']);
Route::get('/getRowTimes/{id}/{GParamID}/{GPid}/{GrapicsID}',  [GraphicTimesController::class, 'getRowTimes']);
Route::get('/getTimes/{id}',  [GraphicTimesController::class, 'getTimes']);
Route::get('/time/{id}',  [GraphicTimesController::class, 'time']);


Route::get('/getForFormuleTimes/{GParamID}',  [GraphicTimesController::class, 'getRowFormuleTimes']);
Route::get('/withCardId/{id}',  [ParamsGraphController::class, 'withCardId']);
Route::get('/getRowBlog/{id}',  [BlogsController::class, 'getRowBlog']);
Route::get('/getForFormuleId/{paramID}/{timeID}',  [CalculatorController::class, 'getForFormuleId']);


