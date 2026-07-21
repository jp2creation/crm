<?php

namespace Modules\CrmCore\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LegacyTemplateController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        return redirect()->to($request->user() ? '/dashboard/crm' : '/login');
    }
}
