"""Executado somente dentro do CT temporário de aplicação."""
from pathlib import Path
import http.cookiejar,json,os,socket,subprocess as sp,sys,time,urllib.request,urllib.error,urllib.parse,re
assert socket.gethostname()=='g360-t2-app-20261101' and Path('/etc/g360-t2-ensaio').exists()
assert all(r.get('dst')=='10.0.0.0/24' for r in json.loads(sp.check_output(['ip','-j','route'])))
jar=http.cookiejar.MozillaCookieJar('/root/t2-cookies.txt')
opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
def req(path,dados=None,token=None,web=False):
 h={'Accept':'application/json'}
 if token:h['Authorization']='Bearer '+token
 h['Content-Type']='application/x-www-form-urlencoded' if web else 'application/json'
 data=(urllib.parse.urlencode(dados).encode() if web else json.dumps(dados).encode()) if dados is not None else None
 r=urllib.request.Request('http://localhost'+path,data=data,headers=h)
 try:res=(opener.open if web else urllib.request.urlopen)(r,timeout=20)
 except urllib.error.HTTPError as e:res=e
 with res:return res.status,res.read(),urllib.parse.urlsplit(res.url).path
modo=sys.argv[1]
r={'ct_isolado':True,'fase':modo}
if modo=='apos-reboot':
 jar.load(ignore_discard=True,ignore_expires=False)
 status,_,path=req('/painel',web=True)
 assert status==200 and path=='/painel'
 r['sessao_preservada_no_reboot']=True
status,body,_=req('/api/v1/auth/login',{'email':'restauracao-t2@example.test','password':'senha-ficticia-restauracao'})
assert status==200;token=json.loads(body)['data']['token'];r['login_api']=status
status,body,_=req('/api/v1/relatorios/rede/1',token=token)
assert status==200 and json.loads(body)['data']['kpis']['notas']==0;r['rede_1']=status
if modo=='primeiro':
 status,_,_=req('/api/v1/relatorios/rede/2',token=token)
 assert status==404;r['rede_2_sem_vinculo']=status
 sp.run(['php','/root/t2-restauracao-completa.php','segunda-rede'],check=True,stdout=sp.DEVNULL)
status,body,_=req('/api/v1/relatorios/rede/2',token=token)
expected=json.loads(Path('/root/t2-inventario.json').read_text())['notas']['contagem']
assert status==200 and json.loads(body)['data']['kpis']['notas']==expected;r['rede_2_com_vinculo']=status
if modo=='primeiro':
 _,body,_=req('/login',web=True)
 csrf=re.search(rb'name="_token"[^>]*value="([^"]+)"',body).group(1).decode()
 status,_,path=req('/login',{'_token':csrf,'email':'restauracao-t2@example.test','password':'senha-ficticia-restauracao'},web=True)
 assert status==200 and path=='/painel'
 jar.save(ignore_discard=True);os.chmod('/root/t2-cookies.txt',0o600)
 r['login_web_sessao_redis']=True
print(json.dumps(r))
