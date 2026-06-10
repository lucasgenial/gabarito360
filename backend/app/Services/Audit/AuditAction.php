<?php

namespace App\Services\Audit;

enum AuditAction: string
{
    case LOGIN_SUCCEEDED = 'auth.login.succeeded';
    case LOGIN_FAILED = 'auth.login.failed';
    case LOGIN_BLOCKED_USER = 'auth.login.blocked_user';
    case LOGIN_BLOCKED_DEVICE = 'auth.login.blocked_device';
    case LOGIN_BLOCKED_VERSION = 'auth.login.blocked_version';
    case LOGIN_RATE_LIMITED = 'auth.login.rate_limited';
    case LOGOUT = 'auth.logout';
    case ACCESS_BLOCKED_USER = 'auth.access.blocked_user';
    case ACCESS_BLOCKED_DEVICE = 'auth.access.blocked_device';
    case ACCESS_BLOCKED_VERSION = 'auth.access.blocked_version';
    case USER_STATUS_CHANGED = 'access.user.status_changed';
    case DEVICE_REVOKED = 'access.device.revoked';
    case PROFILE_GRANTED = 'access.profile.granted';
    case PROFILE_CHANGED = 'access.profile.changed';
    case PROFILE_REVOKED = 'access.profile.revoked';
    case PROFILE_REMOVED = 'access.profile.removed';
}
