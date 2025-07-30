<?php
// routes/web.php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;

Route::get('/test-login', function() {
    $user = App\Models\User::where('email', 'fgv@example.com')->first();
    if (!$user) {
        return response()->json(['error' => 'User not found']);
    }
    if (Hash::check('password', $user->password)) {
        return response()->json(['success' => 'Password is correct', 'user' => $user]);
    } else {
        return response()->json(['error' => 'Password is incorrect']);
    }
});

// Catch-all route for Vue.js SPA - EXCLUDE api routes
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api).*$');  // This excludes routes starting with 'api'
