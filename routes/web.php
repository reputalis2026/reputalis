<?php

use App\Http\Controllers\PulseController;
use App\Http\Controllers\SurveyController;
use App\Models\User;
use App\Support\PanelLocale;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/pulse', [PulseController::class, 'login'])->name('pulse.login');
Route::post('/pulse/login', [PulseController::class, 'authenticate'])->name('pulse.authenticate')->middleware('web');
Route::post('/logout', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/pulse');
})->name('logout')->middleware('web');

Route::middleware(['auth'])->group(function () {
    Route::get('/api/pulse/{client_code}', [PulseController::class, 'metrics'])->name('api.pulse');
    Route::get('/pulse/{client_code}/sw.js', [PulseController::class, 'sw'])->name('pulse.sw');
    Route::get('/pulse/{client_code}/manifest.json', [PulseController::class, 'manifest'])->name('pulse.manifest');
    Route::get('/pulse/{client_code}', [PulseController::class, 'dashboard'])->name('pulse.dashboard');
});

Route::get('/survey', [SurveyController::class, 'show'])->name('survey');
// Encuesta vía NFC: resolución por token
Route::get('/survey/nfc/{token}', [SurveyController::class, 'showNfc'])->name('survey.nfc');
Route::get('/survey/{client_code}/sw.js', [SurveyController::class, 'sw'])->name('survey.sw');
Route::get('/survey/{client_code}', [SurveyController::class, 'show'])->name('survey.client');
Route::get('/manifest/{client_code}.json', [SurveyController::class, 'manifest'])->name('survey.manifest');

Route::get('/admin/language/{locale}', function (Request $request, string $locale) {
    abort_unless(array_key_exists($locale, PanelLocale::supported()), 404);

    $request->session()->put(PanelLocale::SESSION_KEY, $locale);
    app()->setLocale($locale);

    return redirect()->back(fallback: url('/admin'));
})->middleware(['web', 'auth'])->name('panel.language.switch');

/*
 * Ruta POST para el login del panel Filament.
 * El formulario de Filament usa method="post"; si el envío no es interceptado por
 * Livewire (p. ej. sin JS), el navegador hace POST aquí. Esta ruta evita
 * "Method Not Allowed" y procesa el login.
 */
Route::post('/admin/login', function (Request $request) {
    // Filament envía el formulario con statePath 'data' → data[email], data[password], data[remember]
    $email = $request->input('data.email') ?? $request->input('email');
    $password = $request->input('data.password') ?? $request->input('password');
    $remember = $request->boolean('data.remember') || $request->boolean('remember');

    $validated = validator([
        'email' => $email,
        'password' => $password,
    ], [
        'email' => ['required', 'string', 'max:255'],
        'password' => ['required', 'string'],
    ], [
        'email.required' => __('panel.auth.validation.identifier_required'),
        'password.required' => __('panel.auth.validation.password_required'),
    ])->validate();

    $emailKey = strtolower(trim((string) $validated['email']));
    $rateKey = 'filament_admin_login:' . $request->ip() . ':' . $emailKey;
    $maxAttempts = 5;
    $decaySeconds = 60;

    if (RateLimiter::tooManyAttempts($rateKey, $maxAttempts)) {
        return redirect()->route('filament.admin.auth.login')
            ->withInput($request->only('email', 'data'))
            ->withErrors(['email' => __('filament-panels::pages/auth/login.messages.failed')]);
    }

    $user = User::findByIdentifier($validated['email']);
    if (! $user || ! Hash::check($validated['password'], $user->password)) {
        RateLimiter::hit($rateKey, $decaySeconds);
        return redirect()->route('filament.admin.auth.login')
            ->withInput($request->only('email', 'data'))
            ->withErrors(['email' => __('filament-panels::pages/auth/login.messages.failed')]);
    }

    if (! $user->canAccessPanel(Filament::getPanel('admin'))) {
        RateLimiter::hit($rateKey, $decaySeconds);

        $ownedClient = $user->ownedClient;
        $isInactiveClientUser = in_array($user->role, [User::ROLE_CLIENTE, User::ROLE_DISTRIBUIDOR], true)
            && (! $ownedClient || ! $ownedClient->is_active);

        $errorMessage = $isInactiveClientUser
            ? __('panel.auth.inactive_user')
            : __('filament-panels::pages/auth/login.messages.failed');

        return redirect()->route('filament.admin.auth.login')
            ->withInput($request->only('email', 'data'))
            ->withErrors(['email' => $errorMessage]);
    }

    RateLimiter::clear($rateKey);
    Auth::guard('web')->login($user, $remember);
    $request->session()->regenerate();
    $request->session()->save();

    return redirect()->intended('/admin');
})->middleware(['web'])->name('filament.admin.auth.login.post');
