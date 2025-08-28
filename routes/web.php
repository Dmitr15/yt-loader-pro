<?php

use App\Http\Controllers\DownloadController;
use Illuminate\Support\Facades\Route;


//main route
Route::get('/', function () {
    return view('index'); 
})->name('index');

//downloading route
Route::post('/process', [DownloadController::class, 'handleRequest'])->name('process.form');

//route to redirect to download page
Route::get('/download/status/{id}', [DownloadController::class, 'downloadStatus'])->name('download.status');

//route to download file in browser
Route::get('/download/file/{id}', [DownloadController::class, 'downloadFile'])->name('download.file');

//route to check download status in 5 sec
Route::get('/download/check/{id}', [DownloadController::class, 'checkStatus'])->name('download.check');

//route to redirect to download preview page
Route::get('/preview/status/{id}', [DownloadController::class, 'previewStatus'])->name('preview.status');

//route to check preview downloading status 
Route::get('/preview/check/{id}', [DownloadController::class, 'checkPreviewStatus'])->name('preview.check');

//route to return preview data to index page
Route::get('/preview/return/{id}', [DownloadController::class, 'returnPreview'])->name('preview.return');