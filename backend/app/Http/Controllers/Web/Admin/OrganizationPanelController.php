<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Escola;
use App\Models\Nucleo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationPanelController extends Controller
{
    public function __invoke(Request $request): View
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);

        $access = [
            'nucleos' => $actor->can('viewAny', Nucleo::class),
            'escolas' => $actor->can('viewAny', Escola::class),
            'usuarios' => $actor->can('viewAny', User::class),
        ];

        abort_unless(in_array(true, $access, strict: true), 403);

        return view('admin.index', compact('access'));
    }
}
