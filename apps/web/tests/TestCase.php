<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp():void
    {
        parent::setUp();
        // As confirmações de negócio ficam em main; o editor da própria imagem é um dialog compartilhado fora dela.
        \Illuminate\Testing\TestResponse::macro('assertPaginaSemSelecaoPrevia',function(){
            $d=new \DOMDocument();@$d->loadHTML($this->getContent());$x=new \DOMXPath($d);
            \PHPUnit\Framework\Assert::assertSame(0,$x->query('//main//input[@checked]')->length,'A página não deve pré-marcar confirmações.');return $this;
        });
        \Illuminate\Testing\TestResponse::macro('assertPaginaSemUpload',function(){
            $d=new \DOMDocument();@$d->loadHTML($this->getContent());$x=new \DOMXPath($d);
            \PHPUnit\Framework\Assert::assertSame(0,$x->query('//main//input[@type="file"]|//main//form[@enctype="multipart/form-data"]')->length,'O cadastro de aluno continua sem upload de foto.');return $this;
        });
    }
}
