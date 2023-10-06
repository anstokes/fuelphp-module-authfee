<?php

namespace AuthFee\Controller;

use Anstech\Rest\Controller\Cors;
use Anstech\Rest\Helper;
use Auth\Auth;
use Fuel\Core\Cookie;
use Fuel\Core\Input;

class Api extends Cors
{
    // Allowed headers, origin
    protected static $headers = 'Authorization, Content-Type';
    protected static $origin = 'http://localhost:3000';

    // Refresh token configuration
    protected static $refresh_token = null;
    protected static $refresh_token_configuration = [
        'allow'    => true,         // Allow refresh tokens
        'httpOnly' => true,         // Prevent reading by JavaScript
        'secure'   => true,         // HTTPS only
        'sameSite' => 'None',       // None, Lax, Strict
    ];

    protected static function refreshTokenSetting($parameter, $default = '')
    {
        if (isset(static::$refresh_token_configuration[$parameter])) {
            return static::$refresh_token_configuration[$parameter];
        }

        return $default;
    }

    // Allow cross domain requests
    public function after($response)
    {
        $custom_response = parent::after($response);

        // Allow credentials
        $custom_response->set_header('Access-Control-Allow-Credentials', 'true');

        $refresh_token = static::$refresh_token;
        if ($refresh_token && static::refresh_token_setting('allow')) {
            $custom_response->set_header('Set-Cookie', implode('; ', [
                'refreshToken=' . $refresh_token,
                'expires=' . strtotime(Model_Jwt::refresh_token_timeout()),
                'path=/',
                (static::$refresh_token_configuration['secure'] ? 'Secure' : ''),
                (static::$refresh_token_configuration['httpOnly'] ? 'HttpOnly' : ''),
                'samesite=' . static::refresh_token_setting('sameSite'),
            ]));
        }

        return $custom_response;
    }

    public function postLogin()
    {
        // Input input variables
        $username = Input::post('username', Helper::json('username'));
        $password = Input::post('password', Helper::json('password'));

        if ($username && $password) {
            // Attempt login
            if ($user = Auth::validate_user($username, $password)) {
                // Check if used is banned
                if (Auth::member(1, $user)) {
                    $this->http_status = 401;
                    return ['message' => 'User unavailable'];
                }

                // Add refresh token
                static::$refresh_token = Model_Jwt::refresh_token($user);
                return [
                    'user' => [
                        'accessToken' => Model_Jwt::access_token($user),
                        'id'          => $user->id,
                    ],
                ];
            }

            // Invalid credentials
            $this->http_status = 401;
            return ['message' => 'Invalid credentials'];
        }

        $this->http_status = 400;
        if (! $username) {
            // No username provided
            return ['message' => 'No username provided'];
        }

        // No password provided
        return ['message' => 'No password provided'];
    }

    public function postRefreshToken()
    {
        // Check for existence of refreshToken cookier
        if ($refresh_token = Cookie::get('refreshToken')) {
            // Validate refresh token
            if ($claims = Model_Jwt::validate($refresh_token)) {
                // Read user id
                $uid = $claims->get('uid');

                // Find user
                if ($user = \Auth\Model\Auth_User::find($uid)) {
                    // Check if used is banned
                    if (\Auth::member(1, $user)) {
                        $this->http_status = 401;
                        return ['message' => 'User unavailable'];
                    }

                    // Add refresh token
                    static::$refresh_token = Model_Jwt::refresh_token($user);
                    return ['accessToken' => Model_Jwt::access_token($user)];
                }

                // User not found
                $this->http_status = 401;
                return ['message' => 'User not found'];
            }

            // Invalid refresh token
            $this->http_status = 401;
            return ['message' => 'Invalid refresh token'];
        }

        // No refresh token
        $this->http_status = 400;
        return ['message' => 'No refresh token'];
    }

    public function postValidate()
    {
        if ($token = Model_Jwt::read_authorisation_header()) {
            if ($claims = Model_Jwt::validate($token)) {
                $valid = ['message' => 'Valid token'];

                if (Input::post('details')) {
                    foreach ($claims->all() as $key => $value) {
                        if ($value instanceof \DateTimeImmutable) {
                            $valid[$key] = $value->format('U'); // Unix timestamp
                        } else {
                            $valid[$key] = $value;
                        }
                    }
                }

                return $valid;
            }

            $this->http_status = 401;
            return ['message' => 'Invalid credentials'];
        }

        $this->http_status = 400;
        return ['message' => 'No authorisation header'];
    }
}
