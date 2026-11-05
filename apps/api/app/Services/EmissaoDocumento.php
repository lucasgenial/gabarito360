<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmissaoDocumento
{
    public function preparar(Usuario $ator, string $modelo, string $instituicao, ?int $espaco, int $quantidade = 1): array
    {
        $codigo = 'G360-'.strtoupper(bin2hex(random_bytes(16)));
        $data = now()->utc()->format('Y-m-d H:i:s');
        $url = config('documentos.validacao_url');
        abort_unless(filter_var($url, FILTER_VALIDATE_URL) && parse_url($url, PHP_URL_SCHEME) === 'https' && !parse_url($url, PHP_URL_QUERY), 503, 'Validação de documentos indisponível.');
        return [
            'registro' => ['id' => (string) Str::uuid(), 'codigo_hash' => hash('sha256', $codigo),
                'emitido_em' => $data, 'modelo' => $modelo, 'quantidade' => $quantidade,
                'instituicao' => $espaco && DB::table('espacos')->where('id', $espaco)->value('tipo') === 'pessoal' ? 'Espaço pessoal' : mb_substr($instituicao, 0, 255), 'emissor_id' => $ator->id,
                'vinculo_id' => $ator->vinculoContextoId(), 'espaco_id' => $espaco, 'perfil' => $ator->perfil],
            'pdf' => ['codigo' => $codigo, 'url' => $url.'?codigo='.$codigo, 'emissor' => $ator->nome,
                'perfil' => $this->rotulo($ator->perfil), 'contexto' => $instituicao,
                'emitido_em' => \Carbon\CarbonImmutable::parse($data, 'UTC')->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s P')],
        ];
    }

    public function registrar(array $preparado, string $arquivo): void
    {
        $r = $preparado['registro'];
        $r['arquivo_sha256'] = hash_file('sha256', $arquivo);
        $r['selo'] = $this->selo($r, config('app.key'));
        DB::table('documento_emissoes')->insert($r);
    }

    public function verificar(string $codigo, ?string $hash): array
    {
        $r = DB::table('documento_emissoes')->where('codigo_hash', hash('sha256', strtoupper(trim($codigo))))->first();
        if (!$r) return ['estado' => 'nao_encontrado'];
        $dados = (array) $r;
        $integro = false;
        foreach (array_merge([config('app.key')], config('app.previous_keys', [])) as $chave) {
            if (hash_equals($r->selo, $this->selo($dados, $chave))) { $integro = true; break; }
        }
        abort_unless($integro, 503, 'Registro temporariamente indisponível para validação.');
        return ['estado' => $hash === null ? 'emissao_encontrada' : (hash_equals($r->arquivo_sha256, strtolower($hash)) ? 'arquivo_confirmado' : 'arquivo_divergente'),
            'emitido_em' => $r->emitido_em.' UTC', 'modelo' => $r->modelo, 'quantidade' => $r->quantidade,
            'instituicao' => $r->instituicao];
    }

    private function selo(array $r, ?string $chave): string
    {
        if (!$chave) throw new \RuntimeException('Chave de emissão indisponível.');
        $campos = ['id', 'codigo_hash', 'arquivo_sha256', 'emitido_em', 'modelo', 'quantidade', 'instituicao', 'emissor_id', 'vinculo_id', 'espaco_id', 'perfil'];
        $dados = [];
        foreach ($campos as $campo) $dados[$campo] = $r[$campo] === null ? null : (string) $r[$campo];
        $raw = str_starts_with($chave, 'base64:') ? base64_decode(substr($chave, 7), true) : $chave;
        if (!$raw) throw new \RuntimeException('Chave de emissão inválida.');
        return hash_hmac('sha256', json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), hash_hmac('sha256', 'g360-documento-v1', $raw, true));
    }

    private function rotulo(string $perfil): string
    {
        return ['desenvolvedor'=>'Desenvolvedor','suporte'=>'Suporte','financeiro'=>'Financeiro',
            'secretario_escolar'=>'Secretário Escolar','vice_dir_escolar'=>'Vice-diretor escolar',
            'auxiliar_secretaria'=>'Auxiliar de secretaria','secretario_educacao'=>'Secretário de Educação',
            'admin_rede'=>'Administrador da Rede','dir_nucleo'=>'Diretor de Núcleo','dir_escolar'=>'Diretor Escolar',
            'coordenador'=>'Coordenador','professor'=>'Professor','professor_autonomo'=>'Professor autônomo',
            'aplicador'=>'Aplicador','aluno'=>'Aluno'][$perfil] ?? 'Usuário';
    }
}
