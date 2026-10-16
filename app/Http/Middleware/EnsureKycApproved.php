<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureKycApproved
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if ($user && $user->isMerchant()) {
            if (!$user->companyProfile) {
                return redirect()->route('merchant.profile')->with('info', 'Por favor completa la configuración de tu perfil comercial.');
            }
            if ($user->companyProfile->kyc_status !== 'approved') {
                return redirect()->route('merchant.profile')->with('error', 'Tu cuenta está bajo revisión o no ha sido aprobada. Por favor espera a que un Analista valide tu RUT.');
            }
        }
        return $next($request);
    }
}
