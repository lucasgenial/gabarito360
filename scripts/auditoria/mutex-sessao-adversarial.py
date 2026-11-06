"""UR3: exercício local do middleware real, em processos PHP descartáveis; não inicia servidor."""
import argparse,json,os,selectors,subprocess,tempfile,time
from pathlib import Path

PHP=r'''<?php
require $argv[1].'/vendor/autoload.php';
$app=new Illuminate\Foundation\Application($argv[1]);
$app->instance('config',new Illuminate\Config\Repository([
 'app'=>['key'=>'chave-ficticia-exclusiva-deste-teste'],
 'session'=>['cookie'=>'sessao_ficticia','context_lock_path'=>$argv[3],
 'context_lock_topology'=>$argv[7]??'single-host-shared-filesystem'],
]));
$factory=Mockery::mock(Illuminate\Contracts\Routing\ResponseFactory::class);
$factory->shouldReceive('json')->andReturnUsing(fn($data,$status=200,$headers=[])=>new Illuminate\Http\JsonResponse($data,$status,$headers));
$app->instance(Illuminate\Contracts\Routing\ResponseFactory::class,$factory);
require $argv[2];
$r=Illuminate\Http\Request::create('http://ficticio.test/app/probe','POST',[],['sessao_ficticia'=>$argv[4]]);
try {
 $response=(new App\Http\Middleware\SerializarSessao)->handle($r,function()use($argv){
  echo 'ENTER '.hrtime(true).PHP_EOL;flush();usleep((int)((float)$argv[5]*1000000));
  return new Symfony\Component\HttpFoundation\Response('ficticio',200);
 });
 echo 'STATUS '.$response->getStatusCode().PHP_EOL;
} catch(Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
 echo 'STATUS '.$e->getStatusCode().PHP_EOL;
}
'''


def main():
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--qa',required=True,type=Path)
    parser.add_argument('--middleware',required=True,type=Path)
    args=parser.parse_args()
    assert os.name=='posix','Executar somente no QA WSL/Linux local.'
    qa=args.qa.resolve();middleware=args.middleware.resolve()
    assert str(qa).startswith('/tmp/g360-ur3-') and (qa/'vendor/autoload.php').is_file()
    assert middleware.name=='SerializarSessao.php' and middleware.is_file()
    processes=[];cases=[]
    with tempfile.TemporaryDirectory(prefix='g360-ur3-mutex-review-') as tmp:
        root=Path(tmp);assert root.parent==Path('/tmp')
        runner=root/'probe.php';runner.write_text(PHP);locks=root/'locks'
        def start(sid,seconds,topology='single-host-shared-filesystem'):
            # O único processo encerrável pelo teste usa este probe descartável.
            p=subprocess.Popen(['php',str(runner),str(qa),str(middleware),str(locks),sid,str(seconds),'unused',topology],
                stdout=subprocess.PIPE,stderr=subprocess.PIPE,text=True,bufsize=1)
            processes.append(p);return p
        def line(p,timeout=5):
            s=selectors.DefaultSelector();s.register(p.stdout,selectors.EVENT_READ)
            try:
                assert s.select(timeout),'Probe local não respondeu no prazo.'
                result=p.stdout.readline();assert result,result
                return result.strip()
            finally:s.close()
        def finish(p,timeout=20):
            out,err=p.communicate(timeout=timeout)
            assert p.returncode==0,(p.returncode,err)
            return out
        try:
            sid='a'*40;other='b'*40
            holder=start(sid,12);assert line(holder).startswith('ENTER ')
            began=time.monotonic();waiter=start(sid,0)
            independent=start(other,0);assert line(independent).startswith('ENTER ')
            assert 'STATUS 200' in finish(independent,3)
            assert time.monotonic()-began<3,'Sessões distintas foram serializadas juntas.'
            cases.append('sessoes-distintas-paralelas')
            selector=selectors.DefaultSelector();selector.register(waiter.stdout,selectors.EVENT_READ)
            try:assert not selector.select(10.5),'Mesmo SID atravessou o lock antes do holder (>lease10s).'
            finally:selector.close()
            assert 'STATUS 200' in finish(holder,5)
            assert 'ENTER ' in finish(waiter,5)
            cases.append('mesmo-sid-bloqueado-por-12s-sem-lease')
            files=list(locks.glob('*.lock'));assert len(files)==2
            inodes={p.name:p.stat().st_ino for p in files}
            assert all(sid not in p.name and other not in p.name for p in files)

            holder=start(sid,18);assert line(holder).startswith('ENTER ')
            waiter=start(sid,0);out=finish(waiter,17)
            assert 'STATUS 423' in out and 'ENTER ' not in out,out
            assert holder.poll() is None,'Holder terminou antes do limite de15s.'
            assert 'STATUS 200' in finish(holder,5)
            cases.append('timeout15s-423-sem-efeito')

            holder=start(sid,30);assert line(holder).startswith('ENTER ')
            waiter=start(sid,0);time.sleep(.2)
            assert holder.args[1]==str(runner) and root in Path(holder.args[1]).parents
            holder.terminate();holder.communicate(timeout=5)
            assert 'ENTER ' in finish(waiter,5)
            cases.append('morte-holder-libera-fd-no-so')
            assert {p.name:p.stat().st_ino for p in locks.glob('*.lock')}==inodes
            cases.append('lockfiles-persistem-mesmo-inode-sem-sid-cru')

            denied=start(sid,0,topology='multihost-nao-suportado');out=finish(denied,5)
            assert 'STATUS 503' in out and 'ENTER ' not in out
            cases.append('topologia-invalida-falha-fechada')
            print(json.dumps({'somente_local':True,'servidores_acessados':False,'grupos':cases,'total':len(cases)},ensure_ascii=False))
        finally:
            for p in processes:
                if p.poll() is None:
                    assert p.args[1]==str(runner)
                    p.terminate()
                    try:p.communicate(timeout=5)
                    except subprocess.TimeoutExpired:p.kill();p.communicate(timeout=5)


if __name__=='__main__':main()
