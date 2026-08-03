<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            // Exclusive customer portal check on mobile app
            if (session('is_mobile_app') && Auth::user()->role !== 'user') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()->withErrors([
                    'email' => 'Staff and Administrator portal access is not permitted inside the mobile application. Please use a desktop browser.',
                ])->onlyInput('email');
            }
            
            if (Auth::user()->role === 'admin') {
                \App\Models\ActivityLog::log('login', 'Admin logged in: ' . Auth::user()->name);
                return redirect()->intended(route('settings.index'))
                    ->with('notice', 'Welcome back, Admin ' . Auth::user()->name . '!')
                    ->with('noticeType', 'success');
            } else {
                \App\Models\ActivityLog::log('login', 'Customer logged in: ' . Auth::user()->name);
                return redirect()->intended(route('home'))
                    ->with('notice', 'Welcome back, ' . Auth::user()->name . '!')
                    ->with('noticeType', 'success');
            }
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:6',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'user',
        ]);

        Auth::login($user);
        \App\Models\ActivityLog::log('register', 'New customer account registered: ' . $user->name, $user);

        return redirect()->route('home')
            ->with('notice', 'Account created! Welcome, ' . $user->name . '.')
            ->with('noticeType', 'success');
    }

    public function socialLogin(Request $request)
    {
        try {
            $idToken = $request->input('credential');
            $provider = $request->input('provider', 'google');
            $isMobileApp = session('is_mobile_app', false);

            if ($provider !== 'google') {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid authentication parameters.'
                    ], 422);
                }
                return redirect()->route('login')
                    ->withErrors(['email' => 'Invalid authentication parameters.']);
            }

            // Fallback for mobile app or custom simulated Google accounts (when credential/idToken is not provided)
            if (!$idToken) {
                $email = $request->input('email');
                $name = $request->input('name');

                if ($isMobileApp && $email) {
                    $user = User::where('email', $email)->first();

                    if (!$user) {
                        $user = User::create([
                            'name' => $name ?? 'Google User',
                            'email' => $email,
                            'password' => Hash::make(Str::random(32)),
                            'role' => 'user',
                        ]);
                    }

                    Auth::login($user);
                    $request->session()->regenerate();

                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json([
                            'success' => true,
                            'redirect' => route('mobile.home'),
                            'message' => 'Successfully authenticated with Google (Simulated)!'
                        ]);
                    }
                    return redirect()->route('mobile.home');
                }

                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid authentication parameters.'
                    ], 422);
                }
                return redirect()->route('login')
                    ->withErrors(['email' => 'Invalid authentication parameters.']);
            }
            
            $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken);
            $responseJson = null;

            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                $responseJson = curl_exec($ch);
                curl_close($ch);
            }

            if (empty($responseJson)) {
                $responseJson = @file_get_contents($url);
            }
            
            if (!empty($responseJson)) {
                $payload = json_decode($responseJson, true);
                $email = $payload['email'] ?? null;
                $name = $payload['name'] ?? null;
                $emailVerified = $payload['email_verified'] ?? false;
                
                if ($email && ($emailVerified === true || $emailVerified === 'true' || $emailVerified === 1)) {
                    $user = User::where('email', $email)->first();
                    
                    if (!$user) {
                        $user = User::create([
                            'name' => $name ?? 'Google User',
                            'email' => $email,
                            'password' => Hash::make(Str::random(32)),
                            'role' => 'user',
                        ]);
                    }
                    
                    Auth::login($user);
                    $request->session()->regenerate();
                    
                    if ($request->expectsJson() || $request->ajax()) {
                        return response()->json([
                            'success' => true,
                            'redirect' => $isMobileApp ? route('mobile.home') : route('home'),
                            'message' => 'Successfully authenticated with Google!'
                        ]);
                    } else {
                        return redirect()->to($isMobileApp ? route('mobile.home') : route('home'))
                            ->with('notice', 'Successfully authenticated with Google!')
                            ->with('noticeType', 'success');
                    }
                }
            }
            
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to verify Google identity token.'
                ], 422);
            } else {
                return redirect()->route('login')
                    ->withErrors(['email' => 'Failed to verify Google identity token.']);
            }
        } catch (\Throwable $e) {
            Log::error('Social login failure: ' . $e->getMessage());
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication processing failed.'
                ], 500);
            } else {
                return redirect()->route('login')
                    ->withErrors(['email' => 'Authentication processing failed. Details: ' . $e->getMessage()]);
            }
        }
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            \App\Models\ActivityLog::log('logout', ucfirst(Auth::user()->role) . ' logged out: ' . Auth::user()->name);
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')
            ->with('notice', 'Successfully logged out.')
            ->with('noticeType', 'success');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
            ], [
                'email.exists' => 'We could not find an account registered with that email address.',
            ]);

            $email = $request->input('email');
            $user = User::where('email', $email)->first();

            $plainToken = Str::random(60);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token' => Hash::make($plainToken),
                    'created_at' => now(),
                ]
            );

            $resetUrl = route('password.reset', ['token' => $plainToken, 'email' => $email]);

            try {
                Mail::to($email)->send(new PasswordResetMail($user->name, $resetUrl));
            } catch (\Throwable $e) {
                Log::error('Password reset mail send failed: ' . $e->getMessage());
                return back()->withErrors([
                    'email' => 'Unable to send reset email at this moment. Please try again later.',
                ])->withInput();
            }

            return back()->with('notice', 'We have emailed your password reset link!')
                ->with('noticeType', 'success');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('sendResetLinkEmail error: ' . $e->getMessage());
            return back()->withErrors([
                'email' => 'An error occurred while processing your request. Please try again.',
            ])->withInput();
        }
    }

    public function showResetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
        ]);

        $email = $request->input('email');
        $token = $request->input('token');

        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$record || !Hash::check($token, $record->token) || Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            return redirect()->route('password.request')
                ->with('notice', 'This password reset link is invalid or has expired. Please request a new one.')
                ->with('noticeType', 'danger');
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    public function updatePassword(Request $request)
    {
        try {
            $request->validate([
                'token' => 'required|string',
                'email' => 'required|email|exists:users,email',
                'password' => 'required|confirmed|min:6',
            ]);

            $email = $request->input('email');
            $token = $request->input('token');

            $record = DB::table('password_reset_tokens')->where('email', $email)->first();

            if (!$record || !Hash::check($token, $record->token) || Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
                return redirect()->route('password.request')
                    ->with('notice', 'This password reset link is invalid or has expired. Please request a new one.')
                    ->with('noticeType', 'danger');
            }

            $user = User::where('email', $email)->first();
            $user->update([
                'password' => Hash::make($request->input('password')),
            ]);

            DB::table('password_reset_tokens')->where('email', $email)->delete();

            return redirect()->route('login')
                ->with('notice', 'Your password has been successfully updated! You can now log in with your new credentials.')
                ->with('noticeType', 'success');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('updatePassword error: ' . $e->getMessage());
            return back()->withErrors([
                'password' => 'An error occurred while updating your password. Please try again.',
            ])->withInput();
        }
    }

    public function testMail()
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            abort(403);
        }

        try {
            Mail::raw("Mera's Store SMTP connection test was successful!", function ($message) {
                $message->to(Auth::user()->email)
                        ->subject('Store SMTP Mail Test');
            });
            return "<h3>SMTP Connection Success!</h3>The email was successfully sent to " . e(Auth::user()->email) . "!";
        } catch (\Throwable $e) {
            Log::error('SMTP Test Mail error: ' . $e->getMessage());
            return "<h3>SMTP Mail Sending Failed!</h3>Please check application log files for error trace.";
        }
    }
}
