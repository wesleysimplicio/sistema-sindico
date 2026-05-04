<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\Api\AuthController as ApiAuth;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Repositories\UserRepository;

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
            'info'  => Session::flash('login_info'),
            'csrf'  => Session::csrfToken(),
        ]);
    }

    public function submit(): void
    {
        $token = (string) Request::input('_csrf', '');
        if (!Session::verifyCsrf($token)) {
            Session::flash('login_error', 'Sessão expirada. Tente novamente.');
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
            Session::flash('login_error', 'Credenciais inválidas.');
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

    // ---------------------------------------------------------------
    // Password recovery: step 1 — enter CPF/document
    // ---------------------------------------------------------------

    public function forgotPasswordShow(): void
    {
        View::render('auth/forgot-password', [
            'title' => 'Recuperar senha | Sistema Sindico',
            'error' => Session::flash('forgot_error'),
            'info'  => Session::flash('forgot_info'),
            'csrf'  => Session::csrfToken(),
        ]);
    }

    public function forgotPasswordSubmit(): void
    {
        $token = (string) Request::input('_csrf', '');
        if (!Session::verifyCsrf($token)) {
            Session::flash('forgot_error', 'Sessão expirada. Tente novamente.');
            header('Location: /forgot-password');
            return;
        }

        $document = trim((string) Request::input('document', ''));
        if ($document === '') {
            Session::flash('forgot_error', 'Informe seu CPF ou documento cadastrado.');
            header('Location: /forgot-password');
            return;
        }

        $repo = new UserRepository();
        $user = $repo->findByDocument($document);
        if ($user !== null) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $repo->createPasswordResetToken((int) $user['id'], $code, 600);
            error_log(sprintf('[password-reset] user_id=%d document=%s code=%s', (int) $user['id'], $document, $code));
        }

        // Store document in session so the next step knows which user to look up.
        Session::put('reset_document', $document);
        Session::flash('forgot_info', 'Se o documento estiver cadastrado, um código de 6 dígitos foi enviado.');
        header('Location: /verify-code');
    }

    // ---------------------------------------------------------------
    // Password recovery: step 2 — enter 6-digit code
    // ---------------------------------------------------------------

    public function verifyCodeShow(): void
    {
        View::render('auth/verify-code', [
            'title' => 'Verificar código | Sistema Sindico',
            'error' => Session::flash('verify_error'),
            'csrf'  => Session::csrfToken(),
        ]);
    }

    public function verifyCodeSubmit(): void
    {
        $token = (string) Request::input('_csrf', '');
        if (!Session::verifyCsrf($token)) {
            Session::flash('verify_error', 'Sessão expirada. Tente novamente.');
            header('Location: /verify-code');
            return;
        }

        $document = (string) Session::get('reset_document', '');
        $code     = trim((string) Request::input('code', ''));

        if ($document === '' || $code === '') {
            Session::flash('verify_error', 'Código inválido. Reinicie o processo.');
            header('Location: /forgot-password');
            return;
        }

        $repo = new UserRepository();
        $user = $repo->findByDocument($document);
        if ($user === null) {
            Session::flash('verify_error', 'Código inválido ou expirado.');
            header('Location: /verify-code');
            return;
        }

        $row = $repo->findValidResetByCode((int) $user['id'], $code);
        if ($row === null) {
            Session::flash('verify_error', 'Código inválido ou expirado.');
            header('Location: /verify-code');
            return;
        }

        $resetToken = bin2hex(random_bytes(32));
        $repo->attachResetToken((int) $row['id'], $resetToken);

        Session::put('reset_token', $resetToken);
        Session::forget('reset_document');
        header('Location: /reset-password');
    }

    // ---------------------------------------------------------------
    // Password recovery: step 3 — set new password
    // ---------------------------------------------------------------

    public function resetPasswordShow(): void
    {
        if (Session::get('reset_token') === null) {
            header('Location: /forgot-password');
            return;
        }
        View::render('auth/reset-password', [
            'title' => 'Nova senha | Sistema Sindico',
            'error' => Session::flash('reset_error'),
            'csrf'  => Session::csrfToken(),
        ]);
    }

    public function resetPasswordSubmit(): void
    {
        $token = (string) Request::input('_csrf', '');
        if (!Session::verifyCsrf($token)) {
            Session::flash('reset_error', 'Sessão expirada. Tente novamente.');
            header('Location: /reset-password');
            return;
        }

        $resetToken  = (string) Session::get('reset_token', '');
        $newPassword = (string) Request::input('new_password', '');
        $confirm     = (string) Request::input('confirm_password', '');

        if ($resetToken === '') {
            header('Location: /forgot-password');
            return;
        }

        if ($newPassword !== $confirm) {
            Session::flash('reset_error', 'As senhas não conferem.');
            header('Location: /reset-password');
            return;
        }

        $validationError = ApiAuth::validatePassword($newPassword);
        if ($validationError !== null) {
            Session::flash('reset_error', $validationError);
            header('Location: /reset-password');
            return;
        }

        $repo = new UserRepository();
        $row  = $repo->findValidResetByToken($resetToken);
        if ($row === null) {
            Session::flash('reset_error', 'Token inválido ou expirado. Reinicie o processo.');
            header('Location: /forgot-password');
            return;
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $repo->updatePassword((int) $row['user_id'], $hash);
        $repo->markResetTokenUsed((int) $row['id']);
        Session::forget('reset_token');

        Session::flash('login_error', '');
        Session::flash('login_info', 'Senha alterada com sucesso! Faça login.');
        header('Location: /login');
    }
}
