<?php
namespace App\Support;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Registro servidor: novos dashboards reutilizam armazenamento sem aceitar URLs/classes do cliente. */
final class CatalogoPaineis
{
    public static function nomes(): array { return array_keys(config('paineis.paineis', [])); }

    public static function definicao(string $painel): array
    {
        $d=config('paineis.paineis.'.$painel);
        abort_unless(is_array($d),404);
        abort_unless(in_array($d['recurso']??null,['contexto','turma'],true) && is_array($d['secoes_fixas']??null),503);
        return $d;
    }

    public static function seletor(Request $r, string $painel): ?int
    {
        $d=self::definicao($painel);$valores=$r->headers->all('X-G360-Painel-Turma');
        if ($d['recurso']==='contexto') {
            if ($valores) throw ValidationException::withMessages(['painel'=>'Este painel não recebe seletor de turma.']);
            return null;
        }
        $s=count($valores)===1?$valores[0]:null;
        if (!is_string($s) || !preg_match('/^[1-9][0-9]{0,18}$/D',$s)
            || strlen($s)>strlen((string)PHP_INT_MAX) || (strlen($s)===strlen((string)PHP_INT_MAX) && strcmp($s,(string)PHP_INT_MAX)>0)) {
            throw ValidationException::withMessages(['painel'=>'Informe uma turma válida para este painel.']);
        }
        return (int)$s;
    }

    public static function recurso(Usuario $ator, string $painel, ?int $turmaId, bool $travar=false): array
    {
        $d=self::definicao($painel);
        if ($d['recurso']==='contexto') {
            abort_unless($turmaId===null,422);
            return ['recurso_tipo'=>'contexto','recurso_id'=>null];
        }
        abort_unless($turmaId!==null && $turmaId>0,422);
        // Mesma política de leitura do cadastro, inclusive arquivo/administrativos. Sem concessão pedagógica.
        $q=Escopo::turmas($ator);
        if ($travar) $q->lockForUpdate();
        $turma=$q->findOrFail($turmaId);
        return ['recurso_tipo'=>'turma','recurso_id'=>(int)$turma->id];
    }
}
