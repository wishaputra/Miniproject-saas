<?php

namespace App\Livewire\Users;

use App\Services\UserService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Index extends Component
{
    use AuthorizesRequests;

    public $users = [];
    public $showCreateModal = false;

    // Form fields
    public $name = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';

    public function mount(UserService $userService)
    {
        // Only admin can access this page
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $this->loadUsers($userService);
    }

    public function loadUsers(UserService $userService)
    {
        $this->users = $userService->index(auth()->user());
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    public function store(UserService $userService)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $this->validate();

        $userService->store([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
        ], auth()->user());

        $this->reset(['name', 'email', 'password', 'password_confirmation', 'showCreateModal']);
        $this->loadUsers($userService);
        session()->flash('message', 'User added successfully.');
    }

    public function render()
    {
        return view('livewire.users.index');
    }
}
