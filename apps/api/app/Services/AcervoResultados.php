<?php
namespace App\Services;
use App\Models\Usuario;
use App\Support\{ElegibilidadeCopia,Espacos,RegimeVinculos};
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\{Crypt,DB};

final class AcervoResultados
{
    public function consultar(Usuario $ator,int $destino,string $id): array
    {
        abort_unless(Espacos::ativo() && RegimeVinculos::obrigatorio(),403,'Acervo indisponível.');
        ElegibilidadeCopia::lado($ator,$destino,'destino');
        $r=DB::table('acervo_resultados')->where('id',$id)->where('destino_espaco_id',$destino)->first();
        abort_unless($r,404,'Acervo indisponível.');
        try { $json=Crypt::decryptString($r->conteudo_cifrado); $p=json_decode($json,true,64,JSON_THROW_ON_ERROR); }
        catch (DecryptException|\JsonException) { abort(409,'Acervo divergente.'); }
        abort_unless(hash_equals($r->conteudo_digest,hash('sha256',$json)) && is_array($p)
            && ($p['politica']??null)===LotesResultadosCopia::POLITICA && ($p['destino_espaco_id']??null)===$destino
            && ($p['origem_espaco_id']??null)===(int)$r->origem_espaco_id
            && ($p['condicao_historica']??null)==='legado_sem_versao_original',409,'Acervo divergente.');
        return ['acervo_id'=>$id,'publicado_em'=>$r->created_at,'pacote'=>$p];
    }
}
