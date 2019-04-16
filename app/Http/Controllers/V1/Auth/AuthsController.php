<?php

namespace App\Http\Controllers\V1\Auth;

use App\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Http\Request;
use App\Mail\UserActivation;
use App\Mail\UserResetPassword;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Testing\MimeType;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AuthsController extends Controller
{
    protected $guzzle;

    /**
     * Constructor for the controller
     *
     * __construct
     *
     * @param Client $guzzleClient
     */
    public function __construct(Client $guzzleClient)
    {
        $this->guzzle = $guzzleClient;
    }

    /**
     * Get the token array structure.
     *
     * TODO: Research on how to pass HttpOnly cookies to the frontend
     *
     * @param $data
     * @param null $type
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($data, $type = null)
    {
        try {
            if ($type == 'refresh_token') {
                $token = $this->guzzle->post(url('api/v1/oauth/token'), [
                    'form_params' => [
                        'grant_type' => 'refresh_token',
                        'refresh_token' => $data['refresh_token'],
                        'client_id' => env('PASSWORD_GRANT_CLIENT_ID'),
                        'client_secret' => env('PASSWORD_GRANT_CLIENT_SECRET'),
                        'scope' => '',
                    ],
                ]);

                $message = 'Successfully refreshed token';
            } else {
                $token = $this->guzzle->post(url('api/v1/oauth/token'), [
                    'form_params' => [
                        'grant_type' => 'password',
                        'client_id' => env('PASSWORD_GRANT_CLIENT_ID'),
                        'client_secret' => env('PASSWORD_GRANT_CLIENT_SECRET'),
                        'username' => $data['email'],
                        'password' => $data['password'],
                        'scope' => '',
                    ],
                ]);

                $message = 'Successfully logged in';
            }

            $response = json_decode((string) $token->getBody(), true);

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'data' => $response
            ], Response::HTTP_CREATED);
        } catch (BadResponseException $exception) {
            return response()->json([
                'status' => 'error',
                'message' => json_decode((string) $exception->getResponse()->getBody()->getContents(), true)['message'],
                'data' => []
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Register the user and send the activation email
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:6|confirmed|same:password_confirmation',
            'password_confirmation' => 'required|min:6'
        ]);

        if ($validator->fails()) {
            $errorResponse = [];
            $errors = array_map(function ($value) {
                return implode(' ', $value);
            }, $validator->errors()->toArray());
            $errorKeys = $validator->errors()->keys();

            foreach ($errorKeys as $key)
            {
                $array = ['field' => $key, 'error' => $errors[$key]];
                array_push($errorResponse, $array);
            }

            return response()->json(['status' => 'error', 'message' => $errorResponse, 'data' => []], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try
        {
            // Added new user with the given details
            $user = new User([
                'name' => $request->get('first_name').' '.$request->get('last_name'),
                'email' => $request->get('email'),
                'password' => Hash::make($request->get('password')),
                'activation_code' => str_random(64)
            ]);
            $user->save();

            // Assign role 'subscriber' to added user
            $user->roles()->attach(2);

            // Send Activation Mail
            $email = new UserActivation(new User(['name' => $user->name, 'activation_code' => $user->activation_code]));
            Mail::to($user->email)->send($email);

            return response()->json(['status' => 'success', 'message' => 'You have successfully registered. Please click on the activation link sent to your email', 'data' => []], Response::HTTP_CREATED);
        } catch(\Exception $exception) {
            return response()->json(['status' => 'error', 'message' => $exception->getMessage(), 'data' => []], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Login the user and return a token response
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);

        if ($validator->fails()) {
            $errorResponse = [];
            $errors = array_map(function ($value) {
                return implode(' ', $value);
            }, $validator->errors()->toArray());
            $errorKeys = $validator->errors()->keys();

            foreach ($errorKeys as $key)
            {
                $array = ['field' => $key, 'error' => $errors[$key]];
                array_push($errorResponse, $array);
            }

            return response()->json(['status' => 'error', 'message' => $errorResponse, 'data' => []], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $email = $request->get('email');
            $password = $request->get('password');

            // Check and authenticate credentials given
            if ($check = User::query()->where('email', '=', $email)->first()) {
                $activeStatus = $check['is_active'];
                $hashedPassword = $check['password'];

                if ($activeStatus == 1) {
                    if (Hash::check($password, $hashedPassword)) {
                        // Retrieve user details
                        $user = $check;

                        // Update user to status 'online'
                        User::query()->find($user['id'])->update(['is_logged_in' => 1, 'login_at' => Carbon::now()->toDateTimeString()]);

                        $response = $this->respondWithToken($request->toArray());

                        return $response;
                    } else {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Unauthorized. Please enter the correct password',
                            'data' => []
                        ], Response::HTTP_UNAUTHORIZED);
                    }
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Please click on the activation link sent to your email during registration before proceeding',
                        'data' => []
                    ], Response::HTTP_UNAUTHORIZED);
                }
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'An account associated with that email does not exist or has been deactivated',
                    'data' => []
                ], Response::HTTP_NOT_FOUND);
            }
        } catch(\Exception $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
                'data' => []
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Activate the user with the given activation code
     *
     * @param $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function activate($code)
    {
        try {
            $user = User::query()->where('activation_code', '=', $code)->first();

            if ($user) {
                // Activate the user
                $user->activate();

                return response()->json(['status' => 'success', 'message' => 'Successfully activated your account. You may proceed to login', 'data' => []], Response::HTTP_OK);
            } else {
                return response()->json(['status' => 'error', 'message' => 'User with the given code not found', 'data' => []], Response::HTTP_NOT_FOUND);
            }
        } catch(\Exception $exception) {
            return response()->json(['status' => 'error', 'message' => $exception->getMessage(), 'data' => []], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Send a reset password link email
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forgot(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255'
        ]);

        if ($validator->fails()) {
            $errorResponse = [];
            $errors = array_map(function ($value) {
                return implode(' ', $value);
            }, $validator->errors()->toArray());
            $errorKeys = $validator->errors()->keys();

            foreach ($errorKeys as $key)
            {
                $array = ['field' => $key, 'error' => $errors[$key]];
                array_push($errorResponse, $array);
            }

            return response()->json(['status' => 'error', 'message' => $errorResponse, 'data' => []], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            // Retrieve user details
            $user = User::query()->where('email', '=', $request->get('email'))->first();

            if ($user)
            {
                $token = str_random(64);
                DB::table('password_resets')->where('email', '=', $user->email)->delete();
                DB::table('password_resets')->insert(['email' => $user->email, 'token' => $token, 'created_at' => Carbon::now()->toDateTimeString()]);

                // Send Reset Password Mail
                $email = new UserResetPassword(new User(['name' => $user->name, 'email' => $user->email]), $token);
                Mail::to($user->email)->send($email);

                return response()->json(['status' => 'success', 'message' => 'Successfully sent you a reset password link. Please check your email', 'data' => []], Response::HTTP_OK);
            } else {
                return response()->json(['status' => 'error', 'message' => 'An account associated with that email does not exist or has been deactivated', 'data' => []], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch(\Exception $exception) {
            return response()->json(['status' => 'error', 'message' => $exception->getMessage(), 'data' => []], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Reset the password of the account associated with the reset token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|min:64|max:255',
            'password' => 'required|min:6|confirmed|same:password_confirmation',
            'password_confirmation' => 'required|min:6'
        ]);

        if ($validator->fails()) {
            $errorResponse = [];
            $errors = array_map(function ($value) {
                return implode(' ', $value);
            }, $validator->errors()->toArray());
            $errorKeys = $validator->errors()->keys();

            foreach ($errorKeys as $key)
            {
                $array = ['field' => $key, 'error' => $errors[$key]];
                array_push($errorResponse, $array);
            }

            return response()->json(['status' => 'error', 'message' => $errorResponse, 'data' => []], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            // Retrieve password reset details
            $reset = DB::table('password_resets')->where('token', '=', $request->get('token'));

            if ($reset->first()) {
                $email = $reset->first()->email;
                $user = User::query()->where('email', '=', $email)->update(['password' => Hash::make($request->get('password'))]);

                if ($user) {
                    $reset->delete();

                    return response()->json(['status' => 'success', 'message' => 'Successfully reset your password. You may proceed to login', 'data' => []], Response::HTTP_OK);
                } else {
                    return response()->json(['status' => 'error', 'message' => 'Something went wrong. Please try again', 'data' => []], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            } else {
                return response()->json(['status' => 'error', 'message' => 'Token given does not match our records', 'data' => []], Response::HTTP_NOT_FOUND);
            }
        } catch(\Exception $exception) {
            return response()->json(['status' => 'error', 'message' => $exception->getMessage(), 'data' => []], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Refresh the token of the authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request)
    {
        try {
            if ($request->has('refresh')) {
                // Get the refresh token
                $refreshToken = $request->get('refresh');

                // Refresh the expired token
                $response = $this->respondWithToken(['refresh_token' => $refreshToken], 'refresh_token');

                return $response;
            } else {
                return response()->json(['status' => 'error', 'message' => 'Refresh token missing', 'data' => []], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch(\Exception $exception) {
            return response()->json(['status' => 'error', 'message' => $exception->getMessage(), 'data' => []], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse|UserResource
     */
    public function profile()
    {
        try {
            // Retrieve user details
            $userId = app('auth')->user()->id;

            $user = User::query()->findOrFail($userId);

            $resource = new UserResource($user);
            $response = $resource->additional(['status' => 'success', 'message' => 'Successfully retrieved the authenticated user']);

            return $response;
        } catch(\Exception $exception) {
            return response()->json(['status' => 'error', 'message' => $exception->getMessage(), 'data' => []], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the authenticated user's profile.
     *
     * @param Request $request
     * @return UserResource|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'email' => 'required|email|max:255',
            'image_select' => 'sometimes'
        ]);

        if ($validator->fails()) {
            $errorResponse = [];
            $errors = array_map(function ($value) {
                return implode(' ', $value);
            }, $validator->errors()->toArray());
            $errorKeys = $validator->errors()->keys();

            foreach ($errorKeys as $key)
            {
                $array = ['field' => $key, 'error' => $errors[$key]];
                array_push($errorResponse, $array);
            }

            return response()->json(['status' => 'error', 'message' => $errorResponse, 'data' => []], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $userId = app('auth')->id();
            $user = User::query()->findOrFail($userId);

            // Check if an image upload exists in the request
            if($request->has('image_select') && $request->get('image_select') !== '')
            {
                if ($user['profile_image'] !== null)
                {
                    $filename = trim(str_replace(Storage::url(''), '', $user['profile_image']), '/');
                    Storage::disk(env('FILESYSTEM_DRIVER'))->delete($filename);
                }

                $explodeEncodedString = explode('base64,', $request->get('image_select'));
                $mime = trim(str_replace('data:', '', $explodeEncodedString[0]), ';');
                $extension = MimeType::search($mime);
                $filename = Carbon::now()->timestamp.'.'.$extension;
                $image = Storage::url($filename);

                Storage::disk(env('FILESYSTEM_DRIVER'))->put($filename, base64_decode($explodeEncodedString[1]));
            } else {
                $image = $user['profile_image'];
            }

            // Update the user's details
            if ($user['email'] == $request->get('email')) {
                $user->update([
                    'name' => $request->get('first_name').' '.$request->get('last_name'),
                    'profile_image' => $image
                ]);
            } else {
                $user->update([
                    'name' => $request->get('first_name').' '.$request->get('last_name'),
                    'email' => $request->get('email'),
                    'profile_image' => $image
                ]);
            }

            $resource = new UserResource($user);
            $response = $resource->additional(['status' => 'success', 'message' => 'Successfully updated your profile']);

            return $response;
        } catch(\Exception $exception) {
            return response()->json(['status' => 'error', 'message' => $exception->getMessage(), 'data' => []], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Updates the authenticated user's password.
     *
     * @param Request $request
     * @return UserResource|\Illuminate\Http\JsonResponse
     */
    public function password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|min:6',
            'password' => 'required|min:6|confirmed|different:current_password',
            'password_confirmation' => 'required|min:6'
        ]);

        if ($validator->fails()) {
            $errorResponse = [];
            $errors = array_map(function ($value) {
                return implode(' ', $value);
            }, $validator->errors()->toArray());
            $errorKeys = $validator->errors()->keys();

            foreach ($errorKeys as $key)
            {
                $array = ['field' => $key, 'error' => $errors[$key]];
                array_push($errorResponse, $array);
            }

            return response()->json(['status' => 'error', 'message' => $errorResponse, 'data' => []], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $user = app('auth')->user();
            $profileId = $user->id;
            $hashedPassword = $user->password;

            // Check if the current user's password matched the one in the request
            if (Hash::check($request->get('current_password'), $hashedPassword)) {
                $user = User::query()->findOrFail($profileId);

                // Update the password
                $user->update(['password' => Hash::make($request->get('password'))]);

                $resource = new UserResource($user);
                $response = $resource->additional(['status' => 'success', 'message' => 'Successfully updated your password']);

                return $response;
            } else {
                return response()->json(['status' => 'error', 'message' => 'The old password you entered is incorrect', 'data' => []], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch(\Exception $exception) {
            return response()->json(['status' => 'error', 'message' => $exception->getMessage(), 'data' => []], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Logout the authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            // Retrieve user details
            $user = $request->user();

            // Update user to status 'offline'
            User::query()->findOrFail($user['id'])->update(['is_logged_in' => 0, 'logout_at' => Carbon::now()->toDateTimeString()]);

            // Logout user and invalidate token
            $request->user()->token()->revoke();
            $request->user()->token()->delete();

            return response()->json(['status' => 'success', 'message' => 'Successfully logged out', 'data' => []], Response::HTTP_OK);
        } catch(\Exception $exception) {
            return response()->json(['status' => 'error', 'message' => $exception->getMessage(), 'data' => []], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
