<?php

namespace AuthFee\Controller;

use Auth\Auth;
use AuthFee\App;
use AuthFee\Login;
use Fuel\Core\Asset;
use Fuel\Core\Controller;
use Fuel\Core\Input;
use Fuel\Core\Response;
use Fuel\Core\Request;
use Fuel\Core\Session;
use Parser\View;

class Root extends Controller
{
    public function action_index()
    {
        // Check for requested route
        $method_name = 'action_' . Request::active()->param('name', 'login');
        if (method_exists($this, $method_name)) {
            return $this->{$method_name}();
        }

        return $this->action_404();
    }

    public function action_404()
    {
        return $this->response(
            View::forge('screens/404.mustache', [
                'app' => App::configuration(),
            ], false),
            404
        );
    }

    public function action_500()
    {
        return $this->response(
            View::forge('screens/500.mustache', [
                'app' => App::configuration(),
            ], false),
            500
        );
    }

    public function action_login()
    {
        // No message by default
        $message = false;

        // Check if already logged in
        if (Auth::check()) {
            // Check if user has locked screen
            if (Session::get('userLocked')) {
                return $this->actionLock();
            } else {
                // Go back to the page the user came from, or the dashboard if no previous page
                return Response::redirect_back('dashboard');
            }
        }

        // Check if user is attempting login
        if (Input::method() == 'POST') {
            $username = Input::post('username');
            $password = Input::post('password');
            $remember = Input::post('remember');

            // Attempt login
            $login_class = App::parameter('loginClass');
            list($result, $message) = $login_class::attemptLogin($username, $password, $remember);
            if ($result) {
                // Redirect if successful
                return Response::redirect($message);
            }
        }

        // echo "<pre>"; var_dump(Login::formElements()); exit;
        return $this->response(
            View::forge('screens/login.mustache', [
                'app'      => App::configuration(),
                'elements' => Login::formElements(),
                'message'  => $message,
            ], false)
        );
    }

    public function action_logout()
    {
        Auth::logout();
        return Response::redirect('/');
    }

    public function action_forgot()
    {
        return $this->response(
            View::forge('screens/forgot.mustache', [
                'app' => App::configuration(),
            ], false)
        );
    }

    public function action_lock()
    {
        return $this->response(
            View::forge('screens/lock.mustache', [
                'app' => App::configuration(),
            ], false)
        );
    }

    public function action_register()
    {
        return $this->response(
            View::forge('screens/register.mustache', [
                'app' => App::configuration(),
            ], false)
        );
    }

    protected function response($content, $status = 200)
    {
        // Load configuration
        App::configuration();

        // Core scripts
        $scripts = [
            'json5.js',
            'validation.js',
        ];

        // Core stylesheets
        $stylesheets = [
            'bootstrap.min.css',
            'icons.min.css',
            'app.min.css',
            'rounded.css',
        ];

        // Use assets from theme
        $themePath = 'assets' . DS . 'themes' . DS . 'unikit' . DS;
        Asset::add_path($themePath . 'css', 'css');
        Asset::add_path($themePath . 'js', 'js');

        return Response::forge(
            View::forge('template.mustache', [
                'content'     => $content,
                'scripts'     => array_merge(
                    array_map(['\\Fuel\\Core\\Asset', 'js'], $scripts),
                    []
                ),
                'stylesheets' => array_merge(
                    array_map(['\\Fuel\\Core\\Asset', 'css'], $stylesheets),
                    [],
                ),
                'title'       => App::parameter('title'),
                'description' => App::parameter('description'),
                'icon'        => App::parameter('icon'),
            ], false),
            $status
        );
    }
}
