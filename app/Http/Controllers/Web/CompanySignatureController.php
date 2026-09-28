<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\CompanySignature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanySignatureController extends Controller
{
    public function edit(): View
    {
        return view('settings.html.company-signature', [
            'firma' => CompanySignature::base64(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'firma' => ['required', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ]);

        CompanySignature::store($request->file('firma'));

        return redirect()
            ->route('settings.company-signature.edit')
            ->with('status', 'Firma del Gerente General actualizada correctamente.');
    }

    public function destroy(): RedirectResponse
    {
        CompanySignature::delete();

        return redirect()
            ->route('settings.company-signature.edit')
            ->with('status', 'Firma del Gerente General eliminada.');
    }
}
