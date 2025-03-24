<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use App\Models\Company;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;
use App\Models\Enums\RoleUserEnum;

final class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'password' => [
                'required',
                'confirmed',
                Rules\Password::defaults()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->min(8)
            ],
            'company_name' => 'required|string|max:255',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $company = Company::create([
                    'name' => $request->company_name,
                    'email' => $request->email,
                ]);

                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'company_id' => $company->id,
                    'role' => RoleUserEnum::ADMIN,
                    'is_active' => true,
                ]);

                event(new Registered($user));

                Auth::login($user);

                return to_route('dashboard');
            });
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'Ocorreu um erro ao criar sua conta. Por favor, tente novamente.',
            ])->withInput();
        }
    }
}
