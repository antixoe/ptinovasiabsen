<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('auth.password');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed', Rule::notIn([config('auth.default_password')])],
        ], [
            'password.not_in' => 'Kata sandi baru tidak boleh sama dengan kata sandi default.',
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->forceFill([
            'password' => $data['password'],
            'must_change_password' => false,
        ])->save();

        AuditLogger::log('auth.password_changed', $user, [], $user);

        return redirect()->route('dashboard')->with('status', 'Kata sandi berhasil diperbarui.');
    }
}
