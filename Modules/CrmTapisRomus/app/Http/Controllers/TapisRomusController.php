<?php

namespace Modules\CrmTapisRomus\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Modules\CrmTapisRomus\Services\TapisRomusModuleService;

class TapisRomusController extends Controller
{
    public function __invoke(TapisRomusModuleService $module): View
    {
        return view('crm', [
            'crmModule' => $module->payload(),
        ]);
    }
}
