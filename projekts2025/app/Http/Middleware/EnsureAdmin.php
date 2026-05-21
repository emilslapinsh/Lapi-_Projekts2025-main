<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Admin piekļuves filtrs
// Atļauj atvērt admin sadaļu tikai lietotājiem, kuri atbilst admin noteikumam
class EnsureAdmin
{
    // Pārbauda, vai lietotājs ir administrators
    public function handle(Request $request, Closure $next): Response
    {
        // Pašreizējais lietotājs
        $user = $request->user();

        // Ja lietotājs nav admins, bloķē piekļuvi ar 403
        if (! $user || ! $user->isAdmin()) {
            abort(403);
        }

        // Turpina pie nākamā middleware vai kontroliera
        return $next($request);
    }
}
