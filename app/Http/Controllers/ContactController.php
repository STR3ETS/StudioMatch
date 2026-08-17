<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\ContactConfirmation;
use App\Notifications\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{

    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', Rule::in(['general', 'booking', 'studio', 'other'])],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $recipient = (string) config('studio.contact_email');

        if ($recipient !== '') {
            Notification::route('mail', $recipient)->notify(new ContactMessage($validated));
        } else {
            Notification::send(User::where('role', UserRole::Admin)->get(), new ContactMessage($validated));
        }

        Notification::route('mail', [$validated['email'] => $validated['name']])->notify(new ContactConfirmation($validated));

        return redirect()->route('contact')->with('status', __('contact.form.sent'));
    }
}
