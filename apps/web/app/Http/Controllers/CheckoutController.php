<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $dados = $request->validate(['plano_id' => 'nullable|integer']);

        return redirect()->route('leads.index', $dados);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->index($request);
    }

    public function sucesso(Request $request): View
    {
        return view('checkout.sucesso', [
            'status'        => $request->query('status', 'approved'),
            'payment_id'    => $request->query('payment_id'),
            'collection_id' => $request->query('collection_id'),
        ]);
    }

    public function falha(): View
    {
        return view('checkout.falha');
    }

    public function pendente(): View
    {
        return view('checkout.pendente');
    }
}
