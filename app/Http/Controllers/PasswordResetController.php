<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function requestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email', 'max:255']], [
            'email.required' => 'أدخل بريدك الإلكتروني.',
            'email.email' => 'تحقق من صيغة البريد الإلكتروني.',
            'email.max' => 'البريد الإلكتروني طويل جدًا.',
        ]);

        try {
            Password::sendResetLink($request->only('email'));
        } catch (Throwable) {
            // Keep account existence and reset tokens out of logs and responses.
            Log::warning('Midad password reset notification delivery failed.');
            try {
                $user = Password::broker()->getUser($request->only('email'));
                if ($user) {
                    Password::broker()->deleteToken($user);
                }
            } catch (Throwable) {
                // Do not expose mail or token cleanup details to the requester.
            }
        }

        // Same response whether or not the address exists.
        return back()->with('status', 'إن كان البريد مسجّلًا لدينا فستصلك رسالة خلال دقائق.');
    }

    public function resetForm(Request $request, string $token): View|RedirectResponse
    {
        $email = $request->query('email', '');
        $user = is_string($email) && $email !== ''
            ? Password::broker()->getUser(['email' => $email])
            : null;

        if (! $user || ! Password::broker()->tokenExists($user, $token)) {
            return redirect()->route('password.request')->withErrors([
                'email' => 'رابط الاستعادة غير صالح أو انتهت صلاحيته. اطلب رابطًا جديدًا.',
            ]);
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'password_confirmation' => ['required', 'same:password'],
        ], [
            'email.required' => 'أدخل البريد الإلكتروني المرتبط بطلب الاستعادة.',
            'email.email' => 'تحقق من صيغة البريد الإلكتروني.',
            'password.required' => 'أدخل كلمة المرور الجديدة.',
            'password.min' => 'يجب ألا تقل كلمة المرور عن 8 أحرف.',
            'password_confirmation.required' => 'أعد كتابة كلمة المرور الجديدة للتأكيد.',
            'password_confirmation.same' => 'تأكيد كلمة المرور لا يطابق كلمة المرور.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'تم تغيير كلمة المرور. سجّل الدخول بكلمتك الجديدة.');
        }

        return back()->withErrors([
            'email' => 'رابط الاستعادة غير صالح أو انتهت مدته. اطلب رابطًا جديدًا.',
        ])->withInput($request->only('email'));
    }
}
