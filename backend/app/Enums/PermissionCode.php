<?php

namespace App\Enums;

enum PermissionCode: string
{
    case MANAGE_EDUCATION_CENTERS = 'nucleos.gerenciar';
    case MANAGE_SCHOOLS = 'escolas.gerenciar';
    case MANAGE_USERS_PROFILES_LINKS = 'usuarios_perfis_vinculos.gerenciar';
    case MANAGE_CLASSES_STUDENTS = 'turmas_alunos.gerenciar';
    case IMPORT_STUDENTS = 'alunos.importar';
    case VIEW_CLASSES_STUDENTS = 'turmas_alunos.consultar';
    case ASSIGN_CLASS_STAFF = 'turmas_aplicadores.vincular';
    case MANAGE_EXAMS_ANSWER_KEYS = 'provas_gabaritos.gerenciar';
    case CREATE_APPLICATIONS = 'aplicacoes.criar';
    case RUN_APPLICATIONS = 'aplicacoes.executar';
    case CONFIRM_READINGS = 'leituras.confirmar';
    case CORRECT_READINGS_BEFORE_CONFIRMATION = 'leituras.corrigir_antes_confirmacao';
    case VIEW_APPLICATION_DASHBOARD = 'dashboards.aplicacao.consultar';
    case VIEW_EXPORT_CLASS_REPORT = 'relatorios.turma.consultar_exportar_csv';
    case RUN_TECHNICAL_DIAGNOSTICS = 'diagnostico.executar';

    public function description(): string
    {
        return match ($this) {
            self::MANAGE_EDUCATION_CENTERS => 'Gerenciar nucleos.',
            self::MANAGE_SCHOOLS => 'Gerenciar escolas.',
            self::MANAGE_USERS_PROFILES_LINKS => 'Gerenciar usuarios, perfis e vinculos.',
            self::MANAGE_CLASSES_STUDENTS => 'Cadastrar ou alterar turmas e alunos.',
            self::IMPORT_STUDENTS => 'Importar alunos por CSV.',
            self::VIEW_CLASSES_STUDENTS => 'Consultar turmas e alunos.',
            self::ASSIGN_CLASS_STAFF => 'Vincular professor ou aplicador a turma.',
            self::MANAGE_EXAMS_ANSWER_KEYS => 'Criar, editar e publicar prova e gabarito.',
            self::CREATE_APPLICATIONS => 'Vincular prova a turma e criar aplicacao.',
            self::RUN_APPLICATIONS => 'Iniciar ou finalizar aplicacao.',
            self::CONFIRM_READINGS => 'Capturar, revisar e confirmar leitura.',
            self::CORRECT_READINGS_BEFORE_CONFIRMATION => 'Corrigir resposta antes da confirmacao.',
            self::VIEW_APPLICATION_DASHBOARD => 'Consultar dashboard simples da aplicacao.',
            self::VIEW_EXPORT_CLASS_REPORT => 'Consultar relatorio por turma e exportar CSV.',
            self::RUN_TECHNICAL_DIAGNOSTICS => 'Executar diagnostico tecnico.',
        };
    }
}
