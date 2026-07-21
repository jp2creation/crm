<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockedLegacyPhpApiController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort(404);
    }
}
