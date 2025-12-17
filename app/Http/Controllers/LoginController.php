<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UsersAccount;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Handle login form submission
     */
    public function login(Request $request)
    {
        // Validate input
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        // Find user by email
        $user = UsersAccount::where('email', $credentials['email'])->first();

        // Check if user exists and password is correct
        if ($user && \Hash::check($credentials['password'], $user->password)) {
            // Store user in session
            session(['user' => $user]);
            session(['user_id' => $user->id]);
            
            // Redirect to dashboard
            return redirect()->route('admin.dashboard');
        }

        // Login failed - redirect back with error
        return redirect()->route('logout')->with('error', 'Invalid email or password');
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        session()->flush();
        return redirect()->route('logout');
    }
}
