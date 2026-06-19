<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Lead;
use App\Models\Plano;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'plano_id'           => 'nullable|exists:planos,id',
            'nome'               => 'required|string|max:200',
            'email'              => 'required|email|max:150',
            'telefone'           => 'nullable|string|max:20',
            'instituicao'        => 'nullable|string|max:200',
            'cargo'              => 'nullable|string|max:100',
            'licencas_estimadas' => 'nullable|integer|min:1',
            'mensagem'           => 'nullable|string|max:2000',
        ]);

        if (!empty($validated['plano_id'])) {
            $plano = Plano::find($validated['plano_id']);
            if ($plano && $plano->isAutoatendimento()) {
                return ApiResponse::error(
                    'Este plano possui checkout automático. Use o fluxo de assinatura diretamente.',
                    [],
                    422
                );
            }
        }

        $lead = Lead::create($validated);

        return ApiResponse::success(['id' => $lead->id, 'message' => 'Recebemos seu contato! Nossa equipe entrará em contato em breve.'], null, 201);
    }
}
