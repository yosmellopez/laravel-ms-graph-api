<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Auth;
use Ylplabs\LaravelMsGraphApi\Models\MsGraphTokenAPI;

class Microsoft365APISignInListener
{
    public function handle($event)
    {
        $tokenId = $event->token['token_id'];
        $token = MsGraphTokenAPI::find($tokenId)->first();

        if ($token->user_id == null) {
            $user = User::create([
                'name' => $event->token['info']['displayName'],
                'email' => $event->token['info']['mail'],
                'password' => ''
            ]);

            $token->user_id = $user->id;
            $token->save();

            Auth::login($user);

        } else {
            $user = User::findOrFail($token->user_id);
            $user->save();

            Auth::login($user);
        }
    }
}
