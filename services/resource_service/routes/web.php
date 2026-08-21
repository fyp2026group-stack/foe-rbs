<?php

use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\File;

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
    return view('welcome');
});

// Serve storage files directly via Laravel to bypass Docker/Apache symlink issues on Windows
Route::get('/storage/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);
    
    if (!File::exists($fullPath)) {
        return response()->json(['error' => 'File not found', 'attempted_path' => $fullPath], 404);
    }
    
    return response()->file($fullPath);
})->where('path', '.*');

