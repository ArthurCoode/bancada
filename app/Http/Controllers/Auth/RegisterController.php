<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Services\TenantRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request, TenantRegistrationService $registration): RedirectResponse
    {
        $data = $request->validated();

        $user = $registration->register(
            shopName: $data['shop_name'],
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
        );

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home');
    }
}