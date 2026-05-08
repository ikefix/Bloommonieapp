<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;

use Illuminate\Foundation\Auth\RegistersUsers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;

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

        // 🔥 Create User
        $user = $this->create($request->all());

        // 🔥 Send Verification Email
        event(new Registered($user));

        // 🔥 Login User
        Auth::login($user);

        // 🔥 Redirect to verification notice
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

            'password' => Hash::make($data['password']),

            // 🔥 Default Role
            'role' => 'admin',

            // 🔥 Free Trial
            'plan' => 'free_trial',

            'plan_duration' => '3_days',

            'plan_start' => $now,

            'plan_end' => $now->copy()->addDays(3),

            'is_activated' => true,

            'activated_at' => $now,

        ]);

        // 🔥 Owner owns himself
        $user->owner_id = $user->id;
        $user->save();

        return $user;
    }
}