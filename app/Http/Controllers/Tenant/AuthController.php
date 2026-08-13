<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showRegister()
    {
      if (Auth::guard('web')->check())
        return view('tenant.auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:100',
            'email'        => 'required|email|unique:tenants,email',
            'password'     => 'required|string|min:8|confirmed',
            'company_name' => 'nullable|string|max:100',
        ]);

        $tenant = Tenant::create([
            'name'          => $validated['name'],
            'slug'          => Str::slug($validated['name']) . '-' . Str::random(4),
            'email'         => $validated['email'],
            'password'      => Hash::make($validated['password']),
            'company_name'  => $validated['company_name'] ?? null,
            'plan'          => 'trial',
            'trial_ends_at' => now()->addDays(14),
            'is_active'     => true,
        ]);

       Auth::login($tenant);

        return redirect()->route('tenant.dashboard')
            ->with('welcome', true);
    }

    public function showLogin()
    {
        if (Auth::guard('tenant')->check()) {
            return redirect()->route('tenant.dashboard');
        }
        return view('tenant.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::guard('tenant')->attempt(
            $request->only('email', 'password'),
            $request->boolean('remember')
        )) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Invalid email or password.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('tenant.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('tenant')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('tenant.login');
    }
}