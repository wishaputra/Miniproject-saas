<?php

namespace App\Livewire\Auth;

use App\Services\AuthService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Register extends Component
{
    public $company_name = '';
    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';

    public function register(AuthService $authService)
    {
        $validated = $this->validate([
            'company_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $result = $authService->register($validated);
        
        Auth::login($result['user']);
        
        return redirect()->intended('/');
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
