<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;

final class LoginController
{
    public function show(): void
    {
        if (Auth::check()) {
            header('Location: /dashboard');
            return;
        }
        View::render('auth/login', [
            'title' => 'Entrar | Sistema Sindico',
            'error' => Session::flash('login_error'),
            'csrf'  => Session::csrfToken(),
        ]);
    }

    public function submit(): void
    {
        $token = (string) Request::input('_csrf', '');
        if (!Session::verifyCsrf($token)) {
            Session::flash('login_error', 'Sessao expirada. Tente novamente.');
            header('Location: /login');
            return;
        }
        $email    = trim((string) Request::input('email', ''));
        $password = (string) Request::input('password', '');
        if ($email === '' || $password === '') {
            Session::flash('login_error', 'Informe email e senha.');
            header('Location: /login');
            return;
        }
        $user = Auth::attempt($email, $password);
        if ($user === null) {
            Session::flash('login_error', 'Credenciais invalidas.');
            header('Location: /login');
            return;
        }
        header('Location: /dashboard');
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: /login');
    }
}
