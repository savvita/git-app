<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', 'App\Http\Controllers\RepoController@findRepo')->name('home');
Route::post('/repositories', 'App\Http\Controllers\RepoController@repoList')->name('repoList');
Route::get('/repository/{username}/{repoName}', 'App\Http\Controllers\RepoController@repo')->name('repo');
Route::get('/repository/tree/{username}/{repoName}/{sha}', 'App\Http\Controllers\RepoController@repoTree')->name('repoTree');
Route::get('/repository/blob/{username}/{repoName}/{branch}/{filename}/{sha}', 'App\Http\Controllers\RepoController@getBlob')->name('blob');

Route::post('/repository/commit/{sha}', 'App\Http\Controllers\RepoController@getParentCommit')->name('parentCommit');

