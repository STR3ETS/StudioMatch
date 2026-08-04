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

    {{-- Rolfilter --}}
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
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-prussian-blue/10 text-left text-xs font-bold uppercase tracking-wide text-prussian-blue/50">
                            <th class="px-5 py-3.5">{{ __('admin.users.col_name') }}</th>
                            <th class="px-5 py-3.5">{{ __('admin.users.col_role') }}</th>
                            <th class="px-5 py-3.5">{{ __('admin.users.col_bookings') }}</th>
                            <th class="px-5 py-3.5">{{ __('admin.users.col_studios') }}</th>
                            <th class="px-5 py-3.5">{{ __('admin.users.col_registered') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr class="border-b border-prussian-blue/5 last:border-0">
                                <td class="px-5 py-3.5">
                                    <p class="font-semibold text-prussian-blue">{{ $user->name }}</p>
                                    <p class="text-xs text-prussian-blue/50">{{ $user->email }}</p>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span @class([
                                        'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide',
                                        'bg-ruby-red/10 text-ruby-red' => $user->isHost(),
                                        'bg-prussian-blue/5 text-prussian-blue/60' => ! $user->isHost(),
                                    ])>
                                        <i class="fa-solid {{ $user->isHost() ? 'fa-door-open' : 'fa-music' }}"></i>
                                        {{ $user->isHost() ? __('admin.users.role_host') : __('admin.users.role_artist') }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-prussian-blue/70">{{ $user->bookings_count }}</td>
                                <td class="px-5 py-3.5 text-prussian-blue/70">{{ $user->studios_count }}</td>
                                <td class="px-5 py-3.5 text-prussian-blue/70">{{ $user->created_at->translatedFormat('j M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-admin-layout>
