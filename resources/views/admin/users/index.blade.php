<x-admin-layout :title="__('admin.users.title')" active="users">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-prussian-blue">{{ __('admin.users.title') }}</h1>
            <p class="mt-2 text-prussian-blue/60">{{ __('admin.users.subtitle') }}</p>
        </div>
        <a href="{{ route('admin.users.export') }}" class="inline-flex items-center gap-2 rounded-full border border-prussian-blue/20 px-5 py-2.5 text-sm font-semibold text-prussian-blue transition hover:bg-prussian-blue/5">
            <i class="fa-solid fa-file-export fa-sm"></i> {{ __('admin.users.export') }}
        </a>
    </div>

    <form method="GET" action="{{ route('admin.users.index') }}" class="mt-6">
        <select name="role" onchange="this.form.requestSubmit()" class="cursor-pointer rounded-xl border border-prussian-blue/15 bg-white px-3 py-2 text-sm font-medium text-prussian-blue focus:border-prussian-blue/40 focus:outline-none">
            <option value="">{{ __('admin.users.all_roles') }}</option>
            <option value="artiest" @selected($role === 'artiest')>{{ __('admin.users.role_artist') }}</option>
            <option value="verhuurder" @selected($role === 'verhuurder')>{{ __('admin.users.role_host') }}</option>
        </select>
    </form>

    @if ($users->isEmpty())
        <p class="mt-6 rounded-xl border border-dashed border-prussian-blue/20 bg-white px-4 py-8 text-center text-sm text-prussian-blue/50">{{ __('admin.users.empty') }}</p>
    @else
        <div class="mt-6 overflow-hidden rounded-2xl border border-prussian-blue/10 bg-white">
            <div class="overflow-x-auto">
                {{-- Op een telefoon een lijst (naam en rol, de rest als regel eronder), vanaf md de tabel. --}}
                <table class="block w-full text-sm md:table">
                    <thead class="hidden md:table-header-group">
                        <tr class="border-b border-prussian-blue/10 text-left text-xs font-bold uppercase tracking-wide text-prussian-blue/50">
                            <th class="px-5 py-3.5">{{ __('admin.users.col_name') }}</th>
                            <th class="px-5 py-3.5">{{ __('admin.users.col_role') }}</th>
                            <th class="px-5 py-3.5">{{ __('admin.users.col_bookings') }}</th>
                            <th class="px-5 py-3.5">{{ __('admin.users.col_studios') }}</th>
                            <th class="px-5 py-3.5">{{ __('admin.users.col_registered') }}</th>
                        </tr>
                    </thead>
                    <tbody class="block md:table-row-group">
                        @foreach ($users as $user)
                            @php
                                $role = [
                                    'class' => $user->isHost() ? 'bg-ruby-red/10 text-ruby-red' : 'bg-prussian-blue/5 text-prussian-blue/60',
                                    'icon' => $user->isHost() ? 'fa-door-open' : 'fa-music',
                                    'label' => $user->isHost() ? __('admin.users.role_host') : __('admin.users.role_artist'),
                                ];
                            @endphp
                            <tr class="block border-b border-prussian-blue/5 px-4 py-3.5 last:border-0 md:table-row md:p-0">
                                <td class="block md:table-cell md:px-5 md:py-3.5">
                                    {{-- Telefoon: de rol naast de naam, zodat het e-mailadres de hele breedte heeft. --}}
                                    <div class="flex items-start justify-between gap-3">
                                        <p class="min-w-0 font-semibold text-prussian-blue">{{ $user->name }}</p>
                                        <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide md:hidden {{ $role['class'] }}">
                                            <i class="fa-solid {{ $role['icon'] }}"></i> {{ $role['label'] }}
                                        </span>
                                    </div>
                                    <p class="break-words text-xs text-prussian-blue/50">{{ $user->email }}</p>
                                    <p class="mt-1 text-xs text-prussian-blue/70 md:hidden">
                                        {{ collect([
                                            // Een verhuurder boekt zelden: alleen noemen als er boekingen zijn.
                                            ! $user->isHost() || $user->bookings_count > 0 ? trans_choice('admin.users.bookings_count', $user->bookings_count, ['count' => $user->bookings_count]) : null,
                                            $user->isHost() ? trans_choice('admin.users.studios_count', $user->studios_count, ['count' => $user->studios_count]) : null,
                                            __('admin.users.since', ['date' => $user->created_at->translatedFormat('j M Y')]),
                                        ])->filter()->implode(', ') }}
                                    </p>
                                </td>
                                <td class="hidden px-5 py-3.5 md:table-cell">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide {{ $role['class'] }}">
                                        <i class="fa-solid {{ $role['icon'] }}"></i> {{ $role['label'] }}
                                    </span>
                                </td>
                                <td class="hidden px-5 py-3.5 text-prussian-blue/70 md:table-cell">{{ $user->bookings_count }}</td>
                                <td class="hidden px-5 py-3.5 text-prussian-blue/70 md:table-cell">{{ $user->studios_count }}</td>
                                <td class="hidden px-5 py-3.5 text-prussian-blue/70 md:table-cell">{{ $user->created_at->translatedFormat('j M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-admin-layout>
