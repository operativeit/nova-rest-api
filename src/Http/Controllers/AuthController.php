<?php

namespace EomPlus\NovaRestApi\Http\Controllers;

use App\Http\Controllers\Controller;
use EomPlus\NovaRestApi\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

/**
 * @group Authentication
 *
 * APIs for managing users
 */
class AuthController extends Controller
{

    /**
     * Authenticate an user
     *
     * This endpoint lets you authenticate and returns JWT token.
     *
     * @bodyParam username string required The username of the user. Example: user1
     * @bodyParam password string required The password of the user. Example: MyStrongPassword123
     *
     * @response {
     *  "token": "{YOUR_AUTH_KEY}"
     * }
     * @response 400 {
     *  "message": "invalid_credentials"
     * }
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();
        //$credentials+= [ 'enabled' => 1,  'verified' => 1 ];

        $credentialsLdap = [
                'samaccountname' =>  $request->get('username'),
                'password' => $request->get('password'),
        ];

        try {
            if ($token = Auth::guard('api')->attempt($credentialsLdap)) {
                return $this->respondWithToken($token);
            } else if ($token = Auth::guard('api')->attempt($credentials)) {
                return $this->respondWithToken($token);
            } else {
                return response()->json(['message' => 'invalid_credentials'], 400);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Get the token array structure.
     *
     * @param  string  $token
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        $user = Auth::user();

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            'user' => [
            ],
        ]);
    }

    /**
     * Get authenticated user
     *
     * This endpoint returns authenticated user.
     *
     * @authenticated
     *
     * @response {
     *   "user": {
     *     "id": 12131,
     *     "name": "myusername",
     *     "email": "user1@mycompany.com",
     *     "email_verified_at": null,
     *     "created_at": "2021-04-06T16:01:23.000000Z",
     *     "updated_at": "2021-04-06T16:01:23.000000Z"
     *   }
     * }
     */
    public function me()
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                return response()->json(['user_not_found'], 404);
            }
        } catch (Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['token_expired'], $e->getStatusCode());
        } catch (Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['token_invalid'], $e->getStatusCode());
        } catch (Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['token_absent'], $e->getStatusCode());
        }

        return response()->json(compact('user'));
    }

    /**
     * Register as new user
     *
     * This endpoint lets you register a new user.
     *
     * @bodyParam name string required The name of the user. Example: myusername
     * @bodyParam password string required The password of the user. Example: MyStrongPassword123
     * @bodyParam password_confirmation string required The password confirmation of the user. Example: MyStrongPassword123
     * @bodyParam email string required The email of the user. Example: user1@mycompany.com
     *
     * @response {
     *  "token": "{YOUR_AUTH_KEY}"
     * }
     * @response 400 {
     * }
     */
    public function register(Request $request)
    {
        return new JsonResource(['message' => __('user_created')]);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function refresh(Request $request)
    {
        return $this->respondWithToken(JWTAuth::refresh());
    }

    /**
     * Logout user
     *
     * This endpoint disconnect user
     *
     * @authenticated
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        Auth::logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Generate random pin
     *
     * @param  int  $digits
     * @return int
     */
    protected static function randomPin($digits = 8)
    {
        return rand(pow(10, $digits - 1), pow(10, $digits) - 1);
    }

    /**
     * Verify user account
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function verify(Request $request)
    {
        $data = $request->validate([
            'pin' => 'required',
        ]);

        $user = User::where('verify_token', $data['pin'])
           ->where('verified', 0)
           ->first();

        if (is_null($user)) {
            return response()->json(['message' => 'auth.invalid_pin'], 500);
        }

        $user->verify_token = self::randomPin();
        $user->verified = 1;
        $user->save();

        return new JsonResource(['message' => __auth('user_verified')]);
    }

    /**
     * Change password
     *
     * This endpoint change password
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function passwordChange(Request $request)
    {
        return response()->json(['message' => 'auth.do_not_match_the_existing_data'], 404);
    }

    /**
     * Request password change
     *
     * This endpoint request password change
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function requestPasswordChange(Request $request)
    {
        return response()->json(['message' => __('auth.if_user_exists_password_change_pin_sent')]);
    }
}
