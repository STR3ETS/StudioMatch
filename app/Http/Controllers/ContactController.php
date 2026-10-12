<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\ContactConfirmation;
use App\Notifications\ContactMessage;
use App\Rules\Recaptcha;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Throwable;

class ContactController extends Controller
{

    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', Rule::in(['general', 'booking', 'studio', 'other'])],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            // Zonder sleutels geen eis, anders blokkeert het formulier op een omgeving
            // waar reCAPTCHA (nog) niet is ingesteld.
            'g-recaptcha-response' => Recaptcha::enabled()
                ? ['required', new Recaptcha($request->ip())]
                : [],
        ], [
            'g-recaptcha-response.required' => __('contact.form.recaptcha_error'),
        ]);

        // Het token hoort niet in de mail thuis.
        unset($validated['g-recaptcha-response']);

        $recipient = (string) config('studio.contact_email');

        $target = $recipient !== ''
            ? Notification::route('mail', $recipient)
            : User::where('role', UserRole::Admin)->get();

        $this->deliver($target, new ContactMessage($validated));

        $this->deliver(
            Notification::route('mail', [$validated['email'] => $validated['name']]),
            new ContactConfirmation($validated)
        );

        return redirect()->route('contact')->with('status', __('contact.form.sent'));
    }

    /**
     * Verstuurt een contactmail meteen, met de wachtrij als vangnet.
     *
     * Alle notificaties lopen via de wachtrij. Dat is prima voor een boekingsbevestiging,
     * maar een vraag via het contactformulier mag niet stil blijven liggen als er op de
     * server geen wachtrij wordt verwerkt. Daarom eerst direct proberen. Lukt dat niet,
     * bijvoorbeeld omdat de mailprovider even weigert, dan gaat hij alsnog de wachtrij in
     * voor een nieuwe poging, en leggen we vast wat er misging.
     */
    private function deliver(mixed $target, BaseNotification $notification): void
    {
        try {
            Notification::sendNow($target, $notification);
        } catch (Throwable $exception) {
            Log::error('Contactmail kon niet direct worden verstuurd, nu via de wachtrij.', [
                'notification' => $notification::class,
                'error' => $exception->getMessage(),
            ]);

            Notification::send($target, $notification);
        }
    }
}
