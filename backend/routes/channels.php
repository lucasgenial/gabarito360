<?php

use App\Models\Aplicacao;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (string) $user->id === (string) $id;
});

Broadcast::channel('applications.{application}', function ($user, $application) {
    $application = Aplicacao::query()->find($application);

    return $application !== null && $user->can('view', $application);
});
