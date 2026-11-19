<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    /** Avatar tints that exist in the stylesheet. */
    public const AVATAR_COLORS = ['navy', 'slate', 'rose', 'amber', 'violet', 'blue', 'green', 'teal', 'pink', 'orange'];

    public function edit(Request $request): View
    {
        return view('account.edit', [
            'user' => $request->user(),
            'avatarColors' => self::AVATAR_COLORS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'department' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'avatar_color' => ['required', Rule::in(self::AVATAR_COLORS)],
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->update($data);

        AuditLogger::log('user.profile_updated', $user, [
            'name' => $user->name,
            'department' => $user->department,
            'avatar_color' => $user->avatar_color,
        ]);

        return redirect()->route('account.edit')->with('status', 'Profil berhasil diperbarui.');
    }
}
