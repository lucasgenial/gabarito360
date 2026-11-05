<?php
/** Contrato puro de assets públicos: não inicializa Laravel, sessão ou API. */
function g360_publicos_lista(): array
{
    return [
        'css/app.css','css/marca.css','css/apresentacao-publica.css','js/app.js','js/apresentacao-publica.js',
        'fonts/brand/Figtree-ExtraBold.woff2','fonts/brand/AlbertSans-ExtraBold.woff2',
        'fonts/brand/Figtree-OFL.txt','fonts/brand/AlbertSans-OFL.txt',
        'brand/gabarito360-icon.svg','brand/gabarito360-icon-dark.svg',
        'brand/gabarito360-icon.png','brand/gabarito360-og.png',
        'images/avatares/estudantes-v1.png',
        'favicon.ico','favicon.svg','favicon-16x16.png','favicon-32x32.png',
        'favicon-48x48.png','favicon-96x96.png','apple-touch-icon.png',
        'android-chrome-192x192.png','android-chrome-512x512.png','icon-192.png','icon-512.png',
        'maskable-icon-192.png','maskable-icon-512.png','manifest.json','site.webmanifest',
    ];
}

function g360_publicos_arquivo(string $base, string $relativo): string
{
    if (!in_array($relativo,g360_publicos_lista(),true)) throw new RuntimeException('Asset fora do contrato.');
    $caminho=rtrim($base,'/');
    foreach (explode('/',$relativo) as $parte) {
        $caminho.='/'.$parte;
        if (is_link($caminho)) throw new RuntimeException('Symlink não pode ser publicado.');
    }
    if (!is_file($caminho)||filesize($caminho)>5*1024*1024) throw new RuntimeException('Asset ausente ou acima do limite.');
    return $caminho;
}

function g360_publicos_origem(string $origem): string
{
    $p=parse_url($origem);
    if (!$p||!filter_var($origem,FILTER_VALIDATE_URL)||!in_array($p['scheme']??null,['https','http'],true)||empty($p['host'])
        ||isset($p['user'])||isset($p['pass'])||isset($p['query'])||isset($p['fragment'])
        ||!in_array($p['path']??'',['','/'],true)||filter_var($p['host'],FILTER_VALIDATE_IP)) {
        throw new RuntimeException('Informe uma origem pública sem caminho, credenciais ou IP.');
    }
    return rtrim($origem,'/');
}

function g360_publicos_contrato(string $publico): array
{
    if (is_link($publico)||!($base=realpath($publico))) throw new RuntimeException('Pasta pública inválida.');
    $arquivos=[];$hash=hash_init('sha256');hash_update($hash,"g360-marca-estatica-v1\n");
    $lista=g360_publicos_lista();sort($lista,SORT_STRING);
    foreach ($lista as $relativo) {
        $bytes=file_get_contents(g360_publicos_arquivo($base,$relativo));
        if ($bytes===false) throw new RuntimeException('Asset ilegível.');
        if (preg_match('/\.(css|js|svg|json|webmanifest|txt)$/',$relativo)) $bytes=str_replace("\r\n","\n",$bytes);
        $arquivos[$relativo]=$bytes;
        hash_update($hash,strlen($relativo).':'.$relativo.strlen($bytes).':'.$bytes);
    }
    $id=hash_final($hash);$prefixo='/_institucional/marca-'.$id.'/';
    foreach (['manifest.json','site.webmanifest'] as $nome) {
        $m=json_decode($arquivos[$nome],true,512,JSON_THROW_ON_ERROR);
        foreach ($m['icons'] as &$icone) {
            $relativo=ltrim($icone['src'],'/');
            if (!array_key_exists($relativo,$arquivos)) throw new RuntimeException('Ícone do manifest fora do contrato.');
            $icone['src']=$prefixo.$relativo;
        }
        unset($icone);
        $arquivos[$nome]=json_encode($m,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n";
    }
    return ['id'=>$id,'prefixo'=>$prefixo,'css_sha'=>hash('sha256',$arquivos['css/app.css']),'arquivos'=>$arquivos];
}

function g360_publicos_html(string $html,array $contrato,string $origem): string
{
    $origem=g360_publicos_origem($origem);
    // Canonical deve permanecer absoluto; links de navegação continuam relativos à origem.
    return preg_replace_callback('~(<link\b(?=[^>]*\brel="canonical")[^>]*>)|\b(href|src|content)="([^"<>]*)"~i',function($m)use($contrato,$origem){
        if (!empty($m[1])) return $m[1];
        $m=[0=>$m[0],1=>strtolower($m[2]),2=>$m[3]];
        $valor=html_entity_decode($m[2],ENT_QUOTES|ENT_HTML5,'UTF-8');
        $caminho=ltrim(parse_url($valor,PHP_URL_PATH)??'','/');
        if (array_key_exists($caminho,$contrato['arquivos'])) {
            $url=($m[1]==='content'?$origem:'').$contrato['prefixo'].$caminho;
            return $m[1].'="'.htmlspecialchars($url,ENT_QUOTES,'UTF-8').'"';
        }
        if ($m[1]!=='content'&&str_starts_with($valor,$origem)
            &&in_array(substr($valor,strlen($origem),1),['','/','#','?'],true)) {
            $relativo=substr($valor,strlen($origem));
            return $m[1].'="'.htmlspecialchars(str_starts_with($relativo,'/')?$relativo:'/'.$relativo,ENT_QUOTES,'UTF-8').'"';
        }
        return $m[0];
    },$html);
}

function g360_publicos_gravar(string $arquivo,string $bytes): void
{
    if (!is_dir(dirname($arquivo))&&!mkdir(dirname($arquivo),0755,true)) throw new RuntimeException('Pasta de saída indisponível.');
    $h=fopen($arquivo,'xb');if (!$h) throw new RuntimeException('Saída já existe.');
    try {if (fwrite($h,$bytes)!==strlen($bytes)) throw new RuntimeException('Gravação incompleta.');} finally {fclose($h);}
    chmod($arquivo,0644);
}

function g360_publicos_exportar(string $publico,string $destino,string $modelo,string $origem): array
{
    $origem=g360_publicos_origem($origem);
    if (is_link($destino)||!($destino=realpath($destino))||is_link($modelo)||!is_file($modelo)) throw new RuntimeException('Destino/modelo inválido.');
    $c=g360_publicos_contrato($publico);$pasta=$destino.'/marca-'.$c['id'];
    if (file_exists($pasta)||is_link($pasta)) throw new RuntimeException('Bundle já existe.');
    foreach ($c['arquivos'] as $relativo=>$bytes) g360_publicos_gravar($pasta.'/'.$relativo,$bytes);
    $manutencao=str_replace(['__G360_ASSET_PREFIX__','__G360_PUBLIC_ORIGIN__'],[$c['prefixo'],htmlspecialchars($origem,ENT_QUOTES,'UTF-8')],file_get_contents($modelo));
    if (str_contains($manutencao,'__G360_')||preg_match('/<form\b|csrf|name=["\x27]_token/i',$manutencao)) throw new RuntimeException('Manutenção não estática.');
    g360_publicos_gravar($destino.'/manutencao.html',$manutencao);
    g360_publicos_gravar($destino.'/app.css',$c['arquivos']['css/app.css']);
    $manifesto=['id'=>$c['id'],'css_sha'=>$c['css_sha'],'hashes'=>array_map(fn($b)=>hash('sha256',$b),$c['arquivos'])];
    g360_publicos_gravar($destino.'/assets-manifest.json',json_encode($manifesto,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");
    return $manifesto;
}

function g360_publicos_verificar(string $pasta,string $manifesto): void
{
    if (is_link($pasta)||!($base=realpath($pasta))) throw new RuntimeException('Bundle inválido.');
    $m=json_decode(file_get_contents($manifesto),true,512,JSON_THROW_ON_ERROR);
    if (!preg_match('/^[a-f0-9]{64}$/',$m['id']??'')) throw new RuntimeException('Identificador inválido.');
    $lista=array_keys($m['hashes']??[]);$esperada=g360_publicos_lista();sort($lista);sort($esperada);
    if ($lista!==$esperada) throw new RuntimeException('Lista de assets divergente.');
    $itens=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::SELF_FIRST);
    foreach ($itens as $item) {
        if ($item->isLink()) throw new RuntimeException('Symlink no bundle.');
        $relativo=str_replace('\\','/',substr($item->getPathname(),strlen($base)+1));
        if (!$item->isDir()&&(!$item->isFile()||!in_array($relativo,$esperada,true))) throw new RuntimeException('Arquivo extra no bundle.');
    }
    foreach ($m['hashes'] as $relativo=>$hash) {
        if (!hash_equals($hash,hash_file('sha256',g360_publicos_arquivo($base,$relativo)))) throw new RuntimeException('Integridade do bundle divergente.');
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME']??'')===__FILE__) {
    try {
        switch ($argv[1]??'') {
            case 'lista':echo json_encode(g360_publicos_lista(),JSON_THROW_ON_ERROR);break;
            case 'id':echo g360_publicos_contrato($argv[2])['id'];break;
            case 'css':echo g360_publicos_contrato($argv[2])['css_sha'];break;
            case 'exportar':g360_publicos_exportar($argv[2],$argv[3],$argv[4],$argv[5]);break;
            case 'verificar':g360_publicos_verificar($argv[2],$argv[3]);break;
            default:throw new RuntimeException('Comando inválido.');
        }
    } catch (Throwable $e) {fwrite(STDERR,"Assets públicos recusados: ".$e->getMessage()."\n");exit(1);}
}
