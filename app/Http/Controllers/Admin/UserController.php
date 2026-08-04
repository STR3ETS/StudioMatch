<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    /**
     * Gebruikersoverzicht (scope §2.9 admin).
     */
    public function index(Request $request): View
    {
        $role = $request->validate([
            'role' => ['nullable', Rule::in([UserRole::Artiest->value, UserRole::Verhuurder->value])],
        ])['role'] ?? null;

        return view('admin.users.index', [
            'users' => User::query()
                ->where('role', '!=', UserRole::Admin)
                ->when($role, fn ($query) => $query->where('role', $role))
                ->withCount(['bookings', 'studios'])
                ->latest()
                ->get(),
            'role' => $role,
        ]);
    }

    /**
     * Export naar CSV (scope §2.9 admin).
     */
    public function export(): StreamedResponse
    {
        $users = User::where('role', '!=', UserRole::Admin)->withCount(['bookings', 'studios'])->latest()->get();

        return response()->streamDownload(function () use ($users) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['ID', 'Naam', 'E-mail', 'Rol', 'Boekingen', "Studio's", 'Geregistreerd'], ';');

            foreach ($users as $user) {
                fputcsv($handle, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->role->value,
                    $user->bookings_count,
                    $user->studios_count,
                    $user->created_at->format('d-m-Y H:i'),
                ], ';');
            }

            fclose($handle);
        }, 'studiomatch-gebruikers-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=utf-8']);
    }
}
