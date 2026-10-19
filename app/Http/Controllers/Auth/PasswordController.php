<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{

    public function update(Request $request): RedirectResponse
    {
        abort_if($request->user()->isEmployee(), 403, 'Tu contraseña la administra tu gerente, líder o administrador.');
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'force_password_change' => false,
            'auth_version' => $request->user()->auth_version + 1,
            'remember_token' => \Illuminate\Support\Str::random(60),
        ]);
        $request->session()->put('auth_version', $request->user()->auth_version);
        $request->session()->regenerate();

        return back()->with('status', 'password-updated');
    }
}
