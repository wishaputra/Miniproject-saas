<?php

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Projects\Index as ProjectsIndex;
use App\Livewire\Projects\Show as ProjectsShow;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

Route::middleware('auth')->group(function () {
    Route::get('/', ProjectsIndex::class)->name('home');
    Route::get('/projects', App\Livewire\Projects\Index::class)->name('projects.index');
    Route::get('/projects/{project}', App\Livewire\Projects\Show::class)->name('projects.show');
    Route::get('/activity-logs', App\Livewire\ActivityLogs\Index::class)->name('activity-logs.index');
    Route::get('/users', App\Livewire\Users\Index::class)->name('users.index');

    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/login');
    })->name('logout');
});
