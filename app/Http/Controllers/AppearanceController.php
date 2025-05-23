<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;

class AppearanceController extends Controller
{
    /**
     * Update the user's appearance preference.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, string $mode)
    {
        // Validate mode
        if (! in_array($mode, ['light', 'dark', 'system'])) {
            $mode = 'system';
        }

        // Store appearance preference in session
        Session::put('appearance', $mode);

        // Store appearance preference in long-lasting cookie (1 year)
        Cookie::queue('appearance', $mode, 60 * 24 * 365);

        // If this is an AJAX request, return JSON
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'mode' => $mode,
            ]);
        }

        // Otherwise, redirect back
        return redirect()->back();
    }
}
