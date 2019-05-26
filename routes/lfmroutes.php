<?php

$middleware = array_merge(\Config::get('lfm.middlewares'), [
    '\UniSharp\LaravelFilemanager\Middlewares\MultiUser',
    '\App\Http\Middleware\CkEditorCreateDefaultFolder',
]);
$prefix = \Config::get('lfm.url_prefix', \Config::get('lfm.prefix', 'laravel-filemanager'));
$as = 'unisharp.lfm.';
$namespace_lfm = '\UniSharp\LaravelFilemanager\Controllers';

// make sure authenticated
Route::group(compact('middleware', 'prefix', 'as'), function () use ($namespace_lfm) {

    // Show LFM
    Route::get('/', [
        'uses' => $namespace_lfm.'\LfmController@show',
        'as' => 'show',
    ]);

    // Show integration error messages
    Route::get('/errors', [
        'uses' => $namespace_lfm.'\LfmController@getErrors',
        'as' => 'getErrors',
    ]);

    // upload
    Route::any('/upload', [
        'uses' => 'CkEditorUploadController@upload',
        'as' => 'upload',
    ]);

    // list images & files
    Route::get('/jsonitems', [
        'uses' => 'CkEditorController@getItems',
        'as' => 'getItems',
    ]);

    // folders
    Route::get('/newfolder', [
        'uses' => 'CkEditorController@getAddfolder',
        'as' => 'getAddfolder',
    ]);

    Route::get('/folders', [
        'uses' => 'CkEditorController@getFolders',
        'as' => 'getFolders'
    ]);

    // rename
    Route::get('/rename', [
        'uses' => 'CkEditorUploadController@getRename',
        'as' => 'getRename',
    ]);


    // download
    Route::get('/download', [
        'uses' => 'CkEditorController@getDownload',
        'as' => 'getDownload',
    ]);

    // delete
    Route::get('/delete', [
        'uses' => 'CkEditorController@getDelete',
        'as' => 'getDelete',
    ]);

});
