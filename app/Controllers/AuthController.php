<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class AuthController extends BaseController
{
    /**
     * Display the registration form.
     */
    public function registerShow()
    {
        return view('register_form');
    }

    /**
     * Process the registration form submission.
     */
    public function registerAttempt()
    {
        // Validation Rules
        $rules = [
            'email' => [
                'label' => 'Email',
                'rules' => 'required|valid_email|is_unique[users.email]|max_length[254]',
                'errors' => [
                    'is_unique' => 'This email address is already registered.',
                ],
            ],
            'password' => [
                'label' => 'Password',
                'rules' => 'required|min_length[8]',
            ],
            'password_confirm' => [
                'label' => 'Password Confirmation',
                'rules' => 'required|matches[password]',
            ],
        ];

        // Run Validation
        if (!$this->validate($rules)) {
            // Redirect back with validation errors
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        // Validation passed, prepare user data
        $userModel = new UserModel();
        $userData = [
            'email' => $this->request->getPost('email'),
            'password' => $this->request->getPost('password'),
        ];

        // Attempt to save the user (triggers hashPassword callback)
        if (!$userModel->save($userData)) {
            log_message('error', 'User registration failed. Model Errors: ' . print_r($userModel->errors(), true));
            return redirect()->back()->withInput()->with('error', 'Registration failed. Please try again.');
        }

        // Registration successful, Redirect to login page
        return redirect()->to('/login')
            ->with('success', 'Registration successful! Please log in.');

    }

    public function loginShow()
    {
        return view('login_form');
    }

    public function loginAttempt()
    {
        // Define Validation Rules
        $rules = [
            'email' => [
                'label' => 'Email',
                'rules' => 'required|valid_email',
            ],
            'password' => [
                'label' => 'Password',
                'rules' => 'required',
            ],
        ];

        // Run Validation
        if (!$this->validate($rules)) {
            // Redirect back with validation errors
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        // Validation passed, get credentials
        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        // Find user by email
        $userModel = new UserModel();
        $user = $userModel->where('email', $email)->first();

        // Verify User and Password
        if ($user === null || !password_verify($password, $user['password'])) {
            return redirect()->back()->withInput()->with('error', 'Invalid login credentials.');
        }

        // Login Success! Prepare Session Data
        $session = session();
        $sessionData = [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'isLoggedIn' => true,
        ];

        // Set Session Data
        $session->set($sessionData);

        // Redirect to a homepage
        return redirect()->to('/')->with('success', 'Login successful! Welcome back.'); // Redirect to homepage for now

    }

    public function logout()
    {
        $session = session();
        $session->destroy();

        return redirect()->to('/')->with('success', 'You have been logged out successfully.');
    }
}