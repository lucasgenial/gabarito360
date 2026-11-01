<?php

namespace App\Services;

use App\Models\{Cliente, Usuario};
use Illuminate\Support\Facades\{DB, Gate, Validator};
use Illuminate\Validation\{Rule, ValidationException};

/** Persistência comercial. A1 definirá a policy; sem policy o acesso é negado. */
class CadastroCliente
{
    private const CAMPOS = ['tipo', 'nome', 'documento', 'modalidade', 'contato_nome', 'contato_email', 'contato_telefone', 'contrato_referencia'];

    public function cadastrar(Usuario $ator, array $dados): Cliente
    {
        $this->autorizar($ator);
        $dados = $this->validar($dados, true);
        return DB::transaction(function () use ($ator, $dados) {
            $cliente = new Cliente;
            $cliente->forceFill($dados + ['situacao' => 'ativo', 'criado_por' => $ator->id])->save();
            $this->evento($cliente, $ator, 'cadastro', null);
            return $cliente;
        });
    }

    public function atualizar(Usuario $ator, int $id, array $dados): Cliente
    {
        $this->autorizar($ator);
        $dados = $this->validar($dados, false);
        return DB::transaction(function () use ($ator, $id, $dados) {
            $cliente = Cliente::query()->lockForUpdate()->findOrFail($id);
            $anterior = $cliente->modalidade;
            $cliente->forceFill($dados);
            if ($cliente->isDirty()) {
                $mudouModalidade = $cliente->isDirty('modalidade');
                $cliente->save();
                $this->evento($cliente, $ator, $mudouModalidade ? 'modalidade_alterada' : 'cadastro_atualizado', $anterior);
            }
            return $cliente;
        });
    }

    private function autorizar(Usuario $ator): void
    {
        abort_unless($ator->exists && Usuario::query()->whereKey($ator->id)->where('ativo', true)->exists(), 403);
        // Não derivar poder comercial de perfil escolar, pagamento ou propriedade de espaço.
        // Nenhuma definição/concessão desta ability acompanha T4-A.
        Gate::forUser($ator)->authorize('gerir-clientes');
    }

    private function validar(array $dados, bool $criacao): array
    {
        $extras = array_diff(array_keys($dados), self::CAMPOS);
        if ($extras) throw ValidationException::withMessages(['cadastro' => 'O cadastro contém campos não permitidos.']);
        foreach ($dados as $campo => $valor) {
            if (is_string($valor)) $dados[$campo] = trim($valor);
        }
        $obrigatorio = $criacao ? 'required' : 'sometimes';
        return Validator::make($dados, [
            'tipo' => [$obrigatorio, 'required', Rule::in(Cliente::TIPOS)],
            'nome' => [$obrigatorio, 'required', 'string', 'max:200'],
            'modalidade' => [$obrigatorio, 'required', Rule::in(Cliente::MODALIDADES)],
            'documento' => ['nullable', 'string', 'max:80'],
            'contato_nome' => ['nullable', 'string', 'max:200'],
            'contato_email' => ['nullable', 'email:rfc', 'max:254'],
            'contato_telefone' => ['nullable', 'string', 'max:40'],
            'contrato_referencia' => ['nullable', 'string', 'max:120'],
        ])->validate();
    }

    private function evento(Cliente $cliente, Usuario $ator, string $acao, ?string $anterior): void
    {
        // Mesmo commit da alteração: falha no evento desfaz o cadastro.
        // Sem conteúdo livre, documento, contatos, contrato ou payload completo.
        DB::table('cliente_eventos')->insert([
            'cliente_id' => $cliente->id, 'ator_id' => $ator->id, 'acao' => $acao,
            'modalidade_anterior' => $anterior, 'modalidade_nova' => $cliente->modalidade,
            'ocorrido_em' => now(),
        ]);
    }
}
