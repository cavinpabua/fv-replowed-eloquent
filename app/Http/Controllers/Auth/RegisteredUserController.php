<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserMeta;
use App\Models\UserAvatar;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'firstName' => ['required', 'string', 'max:50'],
            'lastName' => ['required', 'string', 'max:50']
        ]);

        // Generate unique UID (range: 1111111111-9999999999)
        $maxRetries = 10;
        $retries = 0;
        $user = null;

        do {
            $newUid = (string) rand(1111111111, 9999999999);

            try {
                $user = User::create([
                    'name' => $request->firstName . ' ' . $request->lastName,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'uid' => $newUid
                ]);
                break;
            } catch (\Illuminate\Database\QueryException $e) {
                if ($e->errorInfo[1] == 1062 && str_contains($e->getMessage(), 'uid')) {
                    $retries++;
                    continue;
                }
                throw $e;
            }
        } while ($retries < $maxRetries);

        if ($user === null) {
            throw new \Exception('Unable to generate unique UID after ' . $maxRetries . ' attempts. Please try again.');
        }

        UserMeta::create([
            'uid' => $newUid,
            'firstName' => request('firstName'),
            'lastName' => request('lastName'),
        ]);

        UserAvatar::create([
            'uid' => $newUid,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('play', absolute: false));
    }
}
