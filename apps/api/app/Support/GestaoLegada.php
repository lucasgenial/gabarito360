<?php

namespace App\Support;

use App\Models\Usuario;

/** Até V1, não permitir que uma ação global na conta afete escopos fora da gestão. */
class GestaoLegada
{
    public static function usuarios(Usuario $gestor)
    {
        if (ContextoVinculo::presente($gestor)) return Usuario::query()->whereIn('usuarios.id', GestaoVinculos::consulta($gestor)->select('v.usuario_id'));
        $escolas = Escopo::escolaIds($gestor) ?? [];
        $turmas = Escopo::turmaIds($gestor) ?? [];
        $mapa = [
            'rede' => $gestor->perfil === 'admin_rede' ? Instituicoes::redesIds($gestor) ?? [] : [],
            'nucleo' => in_array($gestor->perfil, ['admin_rede', 'dir_nucleo'], true) ? Instituicoes::nucleosIds($gestor) ?? [] : [],
            'escola' => $escolas,
            'turma' => $turmas,
            'aluno' => Escopo::alunos($gestor)->select('alunos.id'),
        ];
        $permitidos = function ($q) use ($mapa) {
            foreach ($mapa as $tipo => $ids) {
                $q->orWhere(fn ($s) => $s->where('ue.escopo_tipo', $tipo)->whereIn('ue.escopo_id', $ids));
            }
        };
        return Usuario::query()->where('usuarios.id', '!=', $gestor->id)
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('usuario_vinculos as convertido')->whereColumn('convertido.usuario_id', 'usuarios.id'))
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('usuario_escopos as ue')
                ->whereColumn('ue.usuario_id', 'usuarios.id')->where($permitidos))
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('usuario_escopos as ue')
                ->whereColumn('ue.usuario_id', 'usuarios.id')->whereNot($permitidos));
    }
}
