<?php
$arquivo=storage_path('app/release.json');
$dados=is_file($arquivo)?json_decode(file_get_contents($arquivo),true):[];
if (!is_array($dados) || !is_string($dados['sha']??null) || !is_string($dados['fase']??null) || !preg_match('/^[0-9a-f]{40}$/D',$dados['sha']??'')
    || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,63}$/D',$dados['fase']??'')) return [];
return ['sha'=>$dados['sha'],'fase'=>$dados['fase']];
