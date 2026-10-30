<?php

namespace App\Http\Controllers;

use App\Models\PasswordHelpRequest;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

// Forgot password → emailed link (valid 60 minutes, one request per minute per account) → new password → signed in.
class PasswordResetController extends Controller
{
    // The same answer for every address, so the form never reveals who has an account.
    public const LINK_SENT = 'إن كان البريد مسجّلًا لدينا فستصلك رسالة خلال دقائق. إن لم تجدها فتحقّق من مجلد الرسائل غير المرغوب فيها.';
    // Shown, whoever the address belongs to, while this host cannot send email: the request goes to the admin instead.
    public const HELP_QUEUED = 'وصل طلبك إلى إدارة مِداد إن كان البريد مسجّلًا لدينا. سنرسل لك رابط الاستعادة على واتساب إلى رقم الاستعادة في ملفك، وإن لم تكن أضفت رقمًا فراسلنا على واتساب من الرابط أدناه.';
    public const LINK_INVALID = 'رابط الاستعادة غير صالح أو انتهت صلاحيته. اطلب رابطًا جديدًا.';

    public function requestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $request->merge(['email' => self::normalizeEmail($request->input('email'))]);
        $request->validate(['email' => ['required', 'email', 'max:255']], [
            'email.required' => 'أدخل بريدك الإلكتروني.',
            'email.email' => 'تحقق من صيغة البريد الإلكتروني.',
            'email.max' => 'البريد الإلكتروني طويل جدًا.',
        ]);

        if (! self::mailCanReachInboxes()) {
            $this->askAdmin($request->input('email'));

            return back()->with('status', self::HELP_QUEUED);
        }

        try {
            // Unknown addresses and repeat requests within a minute are answered the same way, without sending.
            Password::sendResetLink($request->only('email'));
        } catch (Throwable $exception) {
            // Never log the address, the token or the transport message (it can contain the SMTP username).
            Log::warning('Midad password reset email could not be delivered.', ['error' => class_basename($exception)]);
            if ($user = Password::broker()->getUser($request->only('email'))) {
                Password::broker()->deleteToken($user);
            }
            // Mail is set up but failed (e.g. the host blocks SMTP): the admin still gets the request. The answer stays
            // the same as for an unknown address, so the form does not reveal who has an account.
            $this->askAdmin($request->input('email'));
        }

        return back()->with('status', self::LINK_SENT);
    }

    public function resetForm(Request $request, string $token): View|RedirectResponse
    {
        $email = self::normalizeEmail($request->query('email'));
        $user = $email !== '' ? Password::broker()->getUser(['email' => $email]) : null;

        if (! $user || ! Password::broker()->tokenExists($user, $token)) {
            return redirect()->route('password.request')->withErrors(['email' => self::LINK_INVALID]);
        }

        return view('auth.reset-password', ['token' => $token, 'email' => $email]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->merge(['email' => self::normalizeEmail($request->input('email'))]);
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email', 'max:255'],
            'password' => User::newPasswordRules(),
            'password_confirmation' => ['required', 'same:password'],
        ], [
            'email.required' => 'أدخل البريد الإلكتروني المرتبط بطلب الاستعادة.',
            'email.email' => 'تحقق من صيغة البريد الإلكتروني.',
            'password.required' => 'أدخل كلمة المرور الجديدة.',
            'password.min' => 'يجب ألا تقل كلمة المرور عن 8 أحرف.',
            'password_confirmation.required' => 'أعد كتابة كلمة المرور الجديدة للتأكيد.',
            'password_confirmation.same' => 'تأكيد كلمة المرور لا يطابق كلمة المرور.',
        ]);

        $resetUser = null;
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) use (&$resetUser): void {
                // A new remember token signs out "remember me" sessions on other devices.
                $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($user));
                $resetUser = $user;
            }
        );

        if ($status !== Password::PASSWORD_RESET || ! $resetUser) {
            return back()->withErrors(['email' => self::LINK_INVALID])->withInput($request->only('email'));
        }

        Auth::login($resetUser);
        $request->session()->regenerate();
        // Whoever was signed in to this account elsewhere (perhaps the reason for the reset) is signed out.
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $resetUser->id)->where('id', '!=', $request->session()->getId())->delete();
        }

        return redirect()->route('home')->with('status', 'تم تغيير كلمة المرور، وأنت الآن داخل حسابك.');
    }

    /** Puts the request in the admin panel (one open request per member) and tells the admins once. */
    private function askAdmin(string $email): void
    {
        $user = Password::broker()->getUser(['email' => $email]);
        if (! $user) {
            return;
        }

        $open = PasswordHelpRequest::query()->open()->where('user_id', $user->id)->first();
        if ($open) {
            $open->touch();

            return;
        }

        PasswordHelpRequest::query()->create(['user_id' => $user->id]);
        User::query()->where('is_admin', true)->whereKeyNot($user->id)->pluck('id')->each(fn ($adminId) => UserNotification::query()->create([
            'user_id' => $adminId,
            'actor_id' => $user->id,
            'type' => 'recovery',
            'message' => 'طلب استعادة كلمة المرور. تجده في لوحة الإدارة.',
        ]));
    }

    public static function normalizeEmail(mixed $email): string
    {
        return is_string($email) ? mb_strtolower(trim($email)) : '';
    }

    // "log" and "array" only write messages locally; outside development they would silently lose every reset email.
    private static function mailCanReachInboxes(): bool
    {
        return ! in_array(config('mail.default'), ['log', 'array'], true) || ! app()->environment('production');
    }
}
