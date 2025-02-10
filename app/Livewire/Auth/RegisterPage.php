<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\Attributes\Title;

#[Title('Register')]
class RegisterPage extends Component
{
    public $name;
    public $email;
    public $password;

    // Regisster user
    public function save()
    {
        // validate input
        $this->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:2|max:255',
        ]);

        // save user to database
        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
        ]);

        // login user
        auth()->login($user);

        // redirect to home page
        return redirect()->intended();

    }

    public function render()
    {
        return view('livewire.auth.register-page');
    }
}
