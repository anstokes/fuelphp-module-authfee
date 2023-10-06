<?php

namespace AuthFee;

use Anstech\FormHelper;
use Auth\Auth;
use Fuel\Core\Package;
use Fuel\Core\Router;
use Model\Auth\User;

class Login extends FormHelper
{
    /**
     * Default avatar location
     * @var string      Path to default avatar
     */
    protected static $default_avatar = 'assets/img/avatars/avatar.png';


    protected static $form_fields = [
        'username' => [
            'id'          => 'username',
            'label'       => 'Username',
            'icon'        => [
                'type'  => 'prefix',
                'class' => 'fa fa-user',
            ],
            'name'        => 'username',
            'type'        => 'text',
            'placeholder' => 'Enter username',
            'validation'  => [
                // array('email' => true),
                [
                    'length' => [
                        3,
                        null,
                    ],
                ],
                ['required' => true],
            ],
            'classes'     => ['wrapper' => 'mb-2'],
        ],
        'password' => [
            'id'          => 'password',
            'label'       => 'Password',
            'icon'        => [
                'type'  => 'prefix',
                'class' => 'fa fa-lock',
            ],
            'name'        => 'password',
            'type'        => 'password',
            'placeholder' => 'Enter password',
            'validation'  => [
                [
                    'length' => [
                        3,
                        null,
                    ],
                ],
                ['required' => true],
            ],
            'classes'     => ['wrapper' => 'mb-2'],
        ],
        'remember' => [
            'id'         => 'remember',
            'label'      => 'Remember me',
            'name'       => 'remember',
            'type'       => 'switch',
            'classes'    => ['wrapper' => 'mt-3'],
            'visibility' => ['hidden'],
        ],
        'login'    => [
            'id'         => 'operation',
            'name'       => 'operation',
            'label'      => 'Log In',
            'icon'       => ['class' => 'fas fa-sign-in-alt ms-1'],
            'value'      => 'login',
            'type'       => 'submit',
            'classes'    => [
                'control' => 'btn btn-primary col-sm-12',
                'wrapper' => 'mb-0',
            ],
            'visibility' => ['hidden'],
        ],
    ];


    public static function attemptLogin($username, $password, $remember = false)
    {
        // Check the credentials
        if (Auth::login($username, $password)) {
            // Did the user want to be remembered
            if ($remember) {
                // Create the remember-me cookie
                Auth::remember_me();
            } else {
                // Delete the remember-me cookie if present
                Auth::dont_remember_me();
            }

            // Check if used is banned
            if (Auth::member(1)) {
                // Logout
                Auth::logout();
                return([false, 'User banned']);
            } else {
                // Set default user company
                list(, $user_id) = Auth::get_user_id();
                return static::successfulLogin($user_id);
            }
        }

        // Invalid credentials
        return [
            false,
            'Invalid credentials',
        ];
    }


    public static function successfulLogin($user_id)
    {
        // Successfully logged in
        return [
            true,
            Router::get('/'),
        ];
    }

    public static function onlineStatus()
    {
        return 'online';
    }


    public static function loggedInAvatar()
    {
        // Avatar handled via profile package, when enabled
        if (Package::loaded('profile') && ($avatar = \Anstech\Profile\Avatar::avatar(static::loggedInId()))) {
            return $avatar;
        }

        // Default avatar image
        return static::loggedInProperty('avatar', static::$default_avatar);
    }


    public static function loggedInEmail($default = null)
    {
        return static::loggedInProperty('email', $default);
    }


    public static function loggedInGroup($default = null)
    {
        if ($group = static::loggedInProperty('group')) {
            return $group->name;
        }

        return $default;
    }


    public static function loggedInId()
    {
        list(, $user_id) = Auth::get_user_id();
        return $user_id;
    }


    public static function loggedInName($default = null)
    {
        if (Package::loaded('profile')) {
            if ($name = \Anstech\Profile\Model\Profile::value('realname')) {
                return $name;
            }

            return static::loggedInUsername($default);
        }

        return $default;
    }


    public static function loggedInUser()
    {
        return User::find(static::loggedInId());
    }


    public static function loggedInUsername($default = null)
    {
        return static::loggedInProperty('username', $default);
    }


    public static function loggedInProperty($property, $default = null)
    {
        if (($user_object = static::loggedInUser()) && isset($user_object->{$property})) {
            return $user_object->{$property};
        }

        return $default;
    }
}
