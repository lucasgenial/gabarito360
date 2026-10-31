<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Mail\EmailModelo;
use App\Models\Lead;
use App\Models\Plano;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class LeadController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'plano_id'           => 'nullable|exists:planos,id',
            'nome'               => 'required|string|max:200',
            'email'              => 'required|email|max:150',
            'assunto'            => 'nullable|string|max:120',
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

        $assunto = $lead->assunto ?: 'Contato pelo site';
        $dados = $lead->only(['nome', 'email', 'telefone', 'instituicao', 'cargo', 'mensagem']) + ['assunto' => $assunto];

        Mail::to(config('emails.contato'))->queue(new EmailModelo('fale-conosco-equipe', "Fale Conosco: {$assunto}", $dados, $lead->email));
        Mail::to($lead->email)->queue(new EmailModelo('fale-conosco-confirmacao', 'Recebemos sua mensagem', [
            'nome' => $lead->nome, 'assunto' => $assunto,
        ]));

        return ApiResponse::success(['id' => $lead->id, 'message' => 'Recebemos seu contato! Nossa equipe entrará em contato em breve.'], null, 201);
    }
}
