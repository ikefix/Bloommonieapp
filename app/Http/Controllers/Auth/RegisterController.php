<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationOtpMail;
use App\Models\EmailVerificationOtp;
use App\Models\User;

use Illuminate\Foundation\Auth\RegistersUsers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

use Illuminate\Http\Request;

use Carbon\Carbon;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users.
    |
    */

    use RegistersUsers;

    /**
     * Redirect after registration
     */
    protected $redirectTo = '/email/verify';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [

            'name' => [
                'required',
                'string',
                'max:255'
            ],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users'
            ],

            'phone' => [
                'nullable',
                'string',
                'max:20',
                'unique:users'
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed'
            ],

        ]);
    }

    /**
     * Register user
     */
    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        // Create User
        $user = $this->create($request->all());

        // =====================================================
        // 📧 CREATE EMAIL VERIFICATION OTP
        // =====================================================

        // Generate 6-digit OTP
        $otp = (string) random_int(100000, 999999);

        // Store hashed OTP
        EmailVerificationOtp::create([
            'user_id' => $user->id,
            'otp_hash' => Hash::make($otp),
            'expires_at' => Carbon::now()->addMinutes(10),
            'attempts' => 0,
        ]);

        // Send OTP email
        Mail::to($user->email)->send(
            new EmailVerificationOtpMail($user, $otp)
        );

        // Login user using normal web session
        Auth::login($user);

        // Redirect to OTP verification page
        return redirect('/email/verify');
    }

    /**
     * Create User
     */
    protected function create(array $data)
    {
        $now = Carbon::now();

        $user = User::create([

            'name' => $data['name'],

            'email' => $data['email'],

            'phone' => $data['phone'] ?? null,

            'password' => Hash::make($data['password']),

            // Default Role
            'role' => 'admin',

            // Free Trial
            'plan' => 'free_trial',

            'plan_duration' => '3_days',

            'plan_start' => $now,

            'plan_end' => $now->copy()->addDays(3),

            'is_activated' => true,

            'activated_at' => $now,

        ]);

        // Owner owns himself
        $user->owner_id = $user->id;
        $user->save();

        return $user;
    }
}

