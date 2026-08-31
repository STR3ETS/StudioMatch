<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\VerifiesAddress;
use App\Models\User;
use App\Rules\FullName;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AccountController extends Controller
{
    use VerifiesAddress;

    public function edit(): View
    {
        return view('account.edit');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $addressRule = $user->isArtist() ? 'required' : 'nullable';

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', new FullName],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'street' => [$addressRule, 'string', 'max:255'],
            'postal_code' => [$addressRule, 'string', 'max:10'],
            'city' => [$addressRule, 'string', 'max:100'],
        ]);

        if ($user->isArtist()) {
            $this->verifiedCoords($validated, __('account.profile.address_invalid'));
        }

        $user->update($validated);

        return redirect()->route('account.edit')->with('status', __('account.profile.saved'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update(['password' => $validated['password']]);

        return redirect()->route('account.edit')->with('status', __('account.password.saved'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'delete_password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        $user->rooms()->with('photos')->get()->each(fn ($room) => $room->photos->each->delete());

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
