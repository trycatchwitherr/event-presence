<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function registerView()
    {
        return view('auth.register');
    }

    public function loginView()
    {
        return view('auth.login');
    }

    public function forgotPasswordView()
    {
        return view('auth.forgot-password');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email:dns|unique:users',
            'username' => 'required|unique:users',
            'nik' => 'required|numeric',
            'password' => 'required|min:5',
            'confirm_password' => 'required',
        ]);

        $nik = Resident::where('nik', $data['nik'])->first();
        if (!$nik) {
            return redirect()->back()->withInput()->withErrors(['nik' => 'NIK not found']);
        }

        if ($data['password'] != $data['confirm_password']) {
            return redirect()->back()->withInput()->withErrors(['confirm_password' => 'Confirm password does not match']);
        }

        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);
        Resident::where('nik', $data['nik'])->update(['user_id' => $user->id]);

        return redirect('/sign-in')->with('message', 'Register success');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'email:dns|required',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('/');
        }

        return back()->with('message', 'Invalid email or password');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/sign-in')->with('message', 'Logout success');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required',
        ]);

        if (!Hash::check($request->old_password, auth()->user()->password)) {
            return back()->with("message", "Old password doesn't match!");
        }

        User::whereId(auth()->user()->id)->update([
            'password' => Hash::make($request->new_password)
        ]);

        return back()->with("message", "Password changed successfully!");
    }
}