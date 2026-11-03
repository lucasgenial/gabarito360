<?php
namespace App\Support\Catalogo;

use Generator;
use RuntimeException;

/** Adaptadores versionados de dados públicos; não grava nem consulta o banco operacional. */
final class LeitorInstituicoes
{
    public const UFS_IBGE = [11=>'RO',12=>'AC',13=>'AM',14=>'RR',15=>'PA',16=>'AP',17=>'TO',21=>'MA',22=>'PI',23=>'CE',24=>'RN',25=>'PB',26=>'PE',27=>'AL',28=>'SE',29=>'BA',31=>'MG',32=>'ES',33=>'RJ',35=>'SP',41=>'PR',42=>'SC',43=>'RS',50=>'MS',51=>'MT',52=>'GO',53=>'DF'];
    public const TIPOS_IES = ['1'=>'universidade','2'=>'centro_universitario','3'=>'faculdade','4'=>'instituto_federal','5'=>'cefet'];
    public const COLUNAS = [
        'basica'=>['NU_ANO_CENSO','CO_ENTIDADE','NO_ENTIDADE','SG_UF','CO_UF','CO_MUNICIPIO','NO_MUNICIPIO','TP_DEPENDENCIA','TP_SITUACAO_FUNCIONAMENTO'],
        'superior'=>['NU_ANO_CENSO','CO_IES','NO_IES','SG_UF_IES','CO_UF_IES','CO_MUNICIPIO_IES','NO_MUNICIPIO_IES','TP_ORGANIZACAO_ACADEMICA','TP_CATEGORIA_ADMINISTRATIVA'],
    ];

    public function __construct(private array $municipios) {}

    /** @return Generator<int,array<string,mixed>> */
    public function ler(string $arquivo, string $fonte, int $ano, string $encoding='Windows-1252'): Generator
    {
        if (!isset(self::COLUNAS[$fonte]) || !in_array($encoding,['Windows-1252','ISO-8859-1','UTF-8'],true)) throw new RuntimeException('Fonte ou encoding não suportado.');
        if (($fonte==='basica' && $ano!==2025) || ($fonte==='superior' && $ano!==2024)) throw new RuntimeException('Edição exige adaptador próprio.');
        $h=fopen($arquivo,'rb');
        if (!$h) throw new RuntimeException('Arquivo público não disponível.');
        try {
            $cab=fgetcsv($h,262144,';','"','');
            if (!$cab) throw new RuntimeException('Cabeçalho ausente.');
            $cab=array_map(fn($c)=>trim(ltrim($c,"\xEF\xBB\xBF")),$cab);
            if (count($cab)!==count(array_unique($cab)) || array_diff(self::COLUNAS[$fonte],$cab)) throw new RuntimeException('Cabeçalho incompatível com o adaptador.');
            $indices = array_flip($cab);
            $selecionadas = self::COLUNAS[$fonte];
            if ($fonte === 'superior' && isset($indices['SG_IES'])) $selecionadas[] = 'SG_IES';
            $linha=1;
            while (($dados=fgetcsv($h,262144,';','"',''))!==false) {
                $linha++;
                if (count($dados)!==count($cab)) throw new RuntimeException("Linha {$linha}: quantidade de campos incompatível.");
                $r = [];
                foreach ($selecionadas as $coluna) {
                    $v = $dados[$indices[$coluna]];
                    if ($encoding==='UTF-8' && !mb_check_encoding($v,'UTF-8')) throw new RuntimeException('Texto UTF-8 inválido.');
                    $r[$coluna] = trim($encoding==='UTF-8'?$v:mb_convert_encoding($v,'UTF-8',$encoding));
                }
                if ($r['NU_ANO_CENSO']!==(string)$ano) throw new RuntimeException("Linha {$linha}: ano divergente.");
                $ies=$fonte==='superior';
                $codigo=$r[$ies?'CO_IES':'CO_ENTIDADE'];
                if (!preg_match($ies?'/^[1-9][0-9]{0,7}$/D':'/^[0-9]{8}$/D',$codigo)) throw new RuntimeException("Linha {$linha}: identificador incompatível.");
                $municipio=$r[$ies?'CO_MUNICIPIO_IES':'CO_MUNICIPIO'];
                $geo=$this->municipios[$municipio] ?? null;
                if (!$geo) throw new RuntimeException("Linha {$linha}: município não conciliado no IBGE.");
                $uf=$r[$ies?'SG_UF_IES':'SG_UF']; $coUf=$r[$ies?'CO_UF_IES':'CO_UF'];
                if (($uf!=='' && $uf!==$geo['uf']) || $coUf!==(string)$geo['uf_codigo']) throw new RuntimeException("Linha {$linha}: UF e município divergentes.");
                $nome=$r[$ies?'NO_IES':'NO_ENTIDADE'];
                if ($nome==='' || mb_strlen($nome)>200) throw new RuntimeException("Linha {$linha}: nome incompatível.");
                $tipo=$ies?(self::TIPOS_IES[$r['TP_ORGANIZACAO_ACADEMICA']] ?? null):'escola';
                if (!$tipo) throw new RuntimeException("Linha {$linha}: tipo de instituição não reconhecido.");
                $situacao=$ies?null:$r['TP_SITUACAO_FUNCIONAMENTO'];
                if (!$ies && !in_array($situacao,['1','2','3','4'],true)) throw new RuntimeException("Linha {$linha}: situação não reconhecida.");
                $sigla=$ies?($r['SG_IES'] ?? null):null;
                if ($sigla!==null && mb_strlen($sigla)>40) throw new RuntimeException("Linha {$linha}: sigla incompatível.");
                yield ['linha'=>$linha,'fonte'=>$fonte,'ano'=>$ano,'namespace'=>$ies?'inep_ies':'inep_escola',
                    'codigo'=>$codigo,'nome'=>$nome,'sigla'=>$sigla?:null,'tipo'=>$tipo,
                    'uf'=>$geo['uf'],'uf_codigo'=>$geo['uf_codigo'],'municipio_ibge'=>$municipio,'municipio_nome'=>$geo['nome'],
                    'uf_derivada_ibge'=>$uf==='','uf_original'=>$uf,'situacao_fonte'=>$situacao,
                    'dependencia_fonte'=>$r[$ies?'TP_CATEGORIA_ADMINISTRATIVA':'TP_DEPENDENCIA'],
                    'natureza_localizacao'=>$ies?'sede_administrativa':'escola',
                    'campos_presentes'=>$cab];
            }
        } finally { fclose($h); }
    }

    /** Geografia pública: admite a hierarquia nova quando a antiga não estiver preenchida. */
    public static function geografia(string $arquivoUfs, string $arquivoMunicipios): array
    {
        $ufs=json_decode(file_get_contents($arquivoUfs),true,512,JSON_THROW_ON_ERROR);
        $lista=json_decode(file_get_contents($arquivoMunicipios),true,512,JSON_THROW_ON_ERROR);
        $porUf=[];
        foreach ($ufs as $u) {
            if (!isset($u['id'],$u['sigla']) || isset($porUf[$u['id']]) || (self::UFS_IBGE[$u['id']] ?? null) !== $u['sigla']) throw new RuntimeException('UF duplicada ou inválida.');
            $porUf[$u['id']]=$u['sigla'];
        }
        $municipios=[];
        foreach ($lista as $m) {
            $uf=$m['microrregiao']['mesorregiao']['UF'] ?? $m['regiao-imediata']['regiao-intermediaria']['UF'] ?? null;
            $id=(string)($m['id']??'');
            if (!preg_match('/^[0-9]{7}$/D',$id) || isset($municipios[$id]) || !$uf || ($porUf[$uf['id']]??null)!==$uf['sigla'] || substr($id,0,2)!==(string)$uf['id']) throw new RuntimeException('Município duplicado ou sem UF coerente.');
            $municipios[$id]=['nome'=>$m['nome'],'uf'=>$uf['sigla'],'uf_codigo'=>$uf['id']];
        }
        return ['ufs'=>$porUf,'municipios'=>$municipios];
    }
}
