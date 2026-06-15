<?php

use App\Models\Aplicacao;
use App\Models\Escola;
use App\Services\Authorization\PortalScope;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (string) $user->id === (string) $id;
});

// Canal privado do próprio usuário (notification.created, report.ready).
Broadcast::channel('users.{userId}', function ($user, $userId) {
    return (string) $user->id === (string) $userId;
});

Broadcast::channel('applications.{application}', function ($user, $application) {
    $application = Aplicacao::query()->find($application);

    return $application !== null && $user->can('view', $application);
});

// Canais escopados de agenda (calendar.event.changed).
Broadcast::channel('escolas.{escola}', function ($user, $escola) {
    $model = Escola::query()->find($escola);

    return $model !== null && app(PortalScope::class)->canViewSchool($user, $model);
});

Broadcast::channel('nucleos.{nucleo}', function ($user, $nucleo) {
    return app(PortalScope::class)->canViewNucleo($user, (string) $nucleo);
});
