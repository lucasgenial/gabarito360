<?php

namespace Tests\Support;

use App\Support\Catalogo\LeitorInstituicoes;

/** Referências fictícias, sem nomes ou dados de pessoas. */
trait ArquivosCatalogo
{
    private array $arquivosCatalogo = [];

    private function arquivoCatalogo(string $conteudo): string
    {
        $arquivo = tempnam(sys_get_temp_dir(), 'ci1-staging-');
        $this->arquivosCatalogo[] = $arquivo;
        file_put_contents($arquivo, $conteudo);
        return $arquivo;
    }

    private function limparArquivosCatalogo(): void
    {
        foreach ($this->arquivosCatalogo as $arquivo) unlink($arquivo);
    }

    private function fonteCatalogo(string $fonte = 'basica'): array
    {
        $ufs = []; $municipios = []; $linhas = [];
        $campos = LeitorInstituicoes::COLUNAS[$fonte];
        if ($fonte === 'superior') $campos[] = 'SG_IES';
        $numero = 0;
        foreach (LeitorInstituicoes::UFS_IBGE as $id => $sigla) {
            $uf = ['id'=>$id,'sigla'=>$sigla]; $ufs[] = $uf; $numero++;
            $municipios[] = ['id'=>(int)($id.'00001'),'nome'=>'Município fictício '.$sigla,'microrregiao'=>['mesorregiao'=>['UF'=>$uf]]];
            $linhas[] = $fonte === 'basica'
                ? [2025,$id.'000001','Escola fictícia '.$sigla,$sigla,$id,$id.'00001','Município fictício '.$sigla,2,1]
                : [2024,$numero,'IES fictícia '.$sigla,$sigla,$id,$id.'00001','Município fictício '.$sigla,($numero % 5)+1,5,'IES'.$sigla];
        }
        $arquivo = $this->arquivoCatalogo(implode(';',$campos)."\n".implode("\n",array_map(fn($r)=>implode(';',$r),$linhas))."\n");
        return ['arquivo'=>$arquivo,'fonte'=>$fonte,'ano'=>$fonte === 'basica' ? 2025 : 2024,
            'ufs'=>$this->arquivoCatalogo(json_encode($ufs)), 'municipios'=>$this->arquivoCatalogo(json_encode($municipios)),
            'sha256'=>hash_file('sha256',$arquivo),'encoding'=>'UTF-8'];
    }
}
