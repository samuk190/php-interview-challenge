<?php

use App\Http\Controllers\API\V1\DogFacts\Rest\DestroyController;
use App\Http\Controllers\API\V1\DogFacts\Rest\IndexController;
use App\Http\Controllers\API\V1\DogFacts\Rest\ShowController;
use App\Http\Controllers\API\V1\DogFacts\Rest\StoreController;
use App\Http\Controllers\API\V1\DogFacts\Rest\UpdateController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'dog-facts'], function () {
    Route::get('{id}', ShowController::class)->name('api.v1.dog-facts.show');
    Route::put('{id}', UpdateController::class)->name('api.v1.dog-facts.update');
    Route::delete('{id}', DestroyController::class)->name('api.v1.dog-facts.destroy');
    Route::post('/', StoreController::class)->name('api.v1.dog-facts.store');
    Route::get('/', IndexController::class)->name('api.v1.dog-facts.index');
});
