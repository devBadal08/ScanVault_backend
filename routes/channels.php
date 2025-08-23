<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{id}', function ($user, $id) {
    // Allow access only if authenticated user id matches
    return (int) $user->id === (int) $id;
});
