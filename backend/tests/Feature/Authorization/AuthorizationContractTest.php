<?php

namespace Tests\Feature\Authorization;

use App\Enums\PermissionCode;
use App\Services\Authorization\AuthorizationContext;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Tests\TestCase;

class AuthorizationContractTest extends TestCase
{
    public function test_every_approved_permission_is_registered_as_a_gate_ability(): void
    {
        foreach (PermissionCode::cases() as $permission) {
            $this->assertTrue(Gate::has($permission->value), "Ability {$permission->value} is not registered.");
        }
    }

    public function test_permission_catalog_explicitly_distinguishes_reads_from_mutations(): void
    {
        $this->assertFalse(PermissionCode::VIEW_CLASSES_STUDENTS->isMutation());
        $this->assertFalse(PermissionCode::VIEW_APPLICATION_DASHBOARD->isMutation());
        $this->assertFalse(PermissionCode::VIEW_EXPORT_CLASS_REPORT->isMutation());
        $this->assertFalse(PermissionCode::VIEW_RESULTS->isMutation());
        $this->assertFalse(PermissionCode::VIEW_REPORTS->isMutation());
        $this->assertFalse(PermissionCode::RUN_TECHNICAL_DIAGNOSTICS->isMutation());
        $this->assertTrue(PermissionCode::MANAGE_CLASSES_STUDENTS->isMutation());
        $this->assertTrue(PermissionCode::MANAGE_SETTINGS->isMutation());
        $this->assertTrue(PermissionCode::CONFIRM_READINGS->isMutation());
        $this->assertTrue(PermissionCode::RUN_APPLICATIONS->requiresOperationalRole());
        $this->assertTrue(PermissionCode::CONFIRM_READINGS->requiresOperationalRole());
        $this->assertFalse(PermissionCode::CREATE_APPLICATIONS->requiresOperationalRole());
    }

    public function test_authorization_context_requires_explicit_operational_and_diagnostic_state(): void
    {
        $unlinked = AuthorizationContext::operational(explicitlyLinked: false);
        $linked = AuthorizationContext::operational(explicitlyLinked: true);
        $audited = AuthorizationContext::diagnostic(auditReference: 'audit-123');

        $this->assertFalse($unlinked->operationallyLinked);
        $this->assertTrue($linked->operationallyLinked);
        $this->assertTrue($audited->isAuditedDiagnostic());
    }

    public function test_diagnostic_context_cannot_exist_without_audit_reference(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AuthorizationContext::diagnostic(auditReference: ' ');
    }
}
