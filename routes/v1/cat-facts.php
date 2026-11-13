<?php

use App\Http\Controllers\API\V1\CatFacts\Rest\DestroyController;
use App\Http\Controllers\API\V1\CatFacts\Rest\IndexController;
use App\Http\Controllers\API\V1\CatFacts\Rest\ShowController;
use App\Http\Controllers\API\V1\CatFacts\Rest\StoreController;
use App\Http\Controllers\API\V1\CatFacts\Rest\UpdateController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'cat-facts'], function () {
    Route::get('{id}', ShowController::class)->whereNumber('id')->name('api.v1.cat-facts.show');
    Route::put('{id}', UpdateController::class)->whereNumber('id')->name('api.v1.cat-facts.update');
    Route::delete('{id}', DestroyController::class)->whereNumber('id')->name('api.v1.cat-facts.destroy');
    Route::post('/', StoreController::class)->name('api.v1.cat-facts.store');
    Route::get('/', IndexController::class)->name('api.v1.cat-facts.index');
});
