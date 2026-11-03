"""Isolamento HTTP real entre duas identidades fictícias, somente no clone9120."""
import json,os,pathlib,urllib.request,urllib.error

base=pathlib.Path('/root/g360-ci1-f-20261103')
assert os.getenv('G360_CI1_CLONE')=='9120' and (base/'clone-9120').read_text().strip()=='9120'
assert pathlib.Path('/etc/hostname').read_text().strip()=='g360-homologa'
for f in pathlib.Path('/sys/class/net').glob('*/operstate'):
    if f.parent.name!='lo':assert f.read_text().strip()=='down'
atores=json.loads((base/'atores.json').read_text())

def req(path,token=None,data=None,expected=200):
    headers={'Accept':'application/json','Host':'homologa.gabarito360.com.br'}
    if token:headers['Authorization']='Bearer '+token
    body=None
    if data is not None:headers['Content-Type']='application/json';body=json.dumps(data).encode()
    q=urllib.request.Request('http://127.0.0.1:8081/api/v1/'+path,data=body,headers=headers)
    try:r=urllib.request.urlopen(q,timeout=20)
    except urllib.error.HTTPError as e:r=e
    assert r.status==expected, path+' retornou '+str(r.status)
    content=r.read()
    return json.loads(content) if content else {}

identidades=[]
try:
    pessoais=[]
    for n,a in enumerate(atores,1):
        i=req('auth/login',data={'email':f'ci1-f-{n}@example.test','password':'Ci1F-Ficticio-2026!'})['data']['token'];identidades.append(i)
        pessoais.append(req('auth/vinculos/selecionar',i,{'vinculo_id':a['vinculo']},201)['data']['token'])
    interna=req('auth/acessos-internos/selecionar',identidades[0],{'acesso_interno_id':atores[0]['interno']},201)['data']['token']
    lista=req('catalogo/minhas-solicitacoes',pessoais[0])['data']['itens'];assert len(lista)==1
    protocolo=lista[0]['id']
    req('catalogo/minhas-solicitacoes/'+protocolo,pessoais[0])
    req('catalogo/minhas-solicitacoes/'+protocolo,pessoais[1],expected=404)
    assert req('catalogo/minhas-solicitacoes',pessoais[1])['data']['itens']==[]
    req('catalogo/atendimento',pessoais[1],expected=403)
    req('alunos',interna,expected=403)
    req('catalogo/atendimento/'+protocolo,interna)
    referencias=req('catalogo/referencias-pessoais',pessoais[1])['data']['itens'];assert referencias==[]
    r={'clone':9120,'login_real_duas_identidades':True,'criacao_pelo_formulario':True,'protocolo_unico':True,
       'proprietario_detalhe':200,'outro_proprietario_detalhe':404,'outra_lista_vazia':True,'outro_pessoal_fila_interna':403,
       'suporte_alunos':403,'suporte_atendimento':200,'referencias_pessoais_isoladas':True}
    (base/'isolamento-http.json').write_text(json.dumps(r,indent=2));print(json.dumps(r))
finally:
    for token in identidades:req('auth/logout',token,{})
