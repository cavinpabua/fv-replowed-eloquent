<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Note: 'global-chat' is a PUBLIC channel - no authorization needed
// Public channels don't need to be defined here
