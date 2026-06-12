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
    case VIEW_RESULTS = 'relatorios.resultados.consultar';
    case VIEW_REPORTS = 'relatorios.consultar';
    case MANAGE_SETTINGS = 'configuracoes.gerenciar';
    case RUN_TECHNICAL_DIAGNOSTICS = 'diagnostico.executar';

    public function isMutation(): bool
    {
        return match ($this) {
            self::VIEW_CLASSES_STUDENTS,
            self::VIEW_APPLICATION_DASHBOARD,
            self::VIEW_EXPORT_CLASS_REPORT,
            self::VIEW_RESULTS,
            self::VIEW_REPORTS,
            self::RUN_TECHNICAL_DIAGNOSTICS => false,
            default => true,
        };
    }

    public function requiresAuditedDiagnosticContext(): bool
    {
        return $this === self::RUN_TECHNICAL_DIAGNOSTICS;
    }

    public function requiresOperationalRole(): bool
    {
        return match ($this) {
            self::RUN_APPLICATIONS,
            self::CONFIRM_READINGS,
            self::CORRECT_READINGS_BEFORE_CONFIRMATION => true,
            default => false,
        };
    }

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
            self::VIEW_RESULTS => 'Consultar resultados individuais autorizados.',
            self::VIEW_REPORTS => 'Consultar e solicitar relatorios canonicos.',
            self::MANAGE_SETTINGS => 'Gerenciar configuracoes administrativas do sistema.',
            self::RUN_TECHNICAL_DIAGNOSTICS => 'Executar diagnostico tecnico.',
        };
    }
}
