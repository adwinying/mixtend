<?php

use App\Http\Controllers\ScheduleIndexController;
use Illuminate\Support\Facades\Route;

Route::get('/', ScheduleIndexController::class)->name('home');
