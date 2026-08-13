<?php

namespace App\Http\Controllers\Host;

use App\Enums\OwnerType;
use App\Http\Controllers\Controller;
use App\Models\HostProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{

    public function edit(Request $request): View
    {
        return view('host.profile', [
            'profile' => $request->user()->hostProfile ?? new HostProfile,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'owner_type' => ['required', Rule::enum(OwnerType::class)],
            'btw_plichtig' => ['required', 'boolean'],
            'kvk_number' => ['nullable', 'string', 'max:20', 'required_if:owner_type,ondernemer'],
            'vat_number' => ['nullable', 'string', 'max:30', 'required_if:btw_plichtig,1'],
        ]);

        $request->user()->hostProfile()->updateOrCreate([], $validated);

        return redirect()->route('host.profile.edit')->with('status', __('host.profile.saved'));
    }
}
