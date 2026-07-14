<?php

namespace Modules\CrmCore\Http\Controllers;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class LegacyTemplateController extends Controller
{
    public function __invoke(): Response
    {
        abort(404);
    }
}
