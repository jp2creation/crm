<?php

namespace Modules\CrmCore\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LegacyCrmPathRedirectController extends Controller
{
    public function __invoke(Request $request, string $legacyPath): RedirectResponse
    {
        $targetPath = preg_replace('#^crm/#', '', trim($legacyPath, '/')) ?: '';
        $target = '/'.$targetPath;
        $query = $request->getQueryString();

        if ($query) {
            $target .= '?'.$query;
        }

        return redirect()->to($target);
    }
}
