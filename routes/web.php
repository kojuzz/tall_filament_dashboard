<?php

use App\Livewire\HomePage;
use Illuminate\Support\Facades\Route;

Route::view('/welcome', 'welcome');
Route::get('/', HomePage::class);

