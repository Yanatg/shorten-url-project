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
        // 1. Define Validation Rules
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
                'rules' => 'required|min_length[8]', // Add more complexity rules if desired
            ],
            'password_confirm' => [
                'label' => 'Password Confirmation',
                'rules' => 'required|matches[password]',
            ],
        ];

        // 2. Run Validation
        if (!$this->validate($rules)) {
            // Redirect back with validation errors
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        // 3. Validation passed, prepare user data
        $userModel = new UserModel();
        $userData = [
            'email' => $this->request->getPost('email'),
            'password' => $this->request->getPost('password'), // Pass plain text, model callback will hash it
        ];

        // 4. Attempt to save the user (triggers hashPassword callback)
        if (!$userModel->save($userData)) {
            log_message('error', 'User registration failed. Model Errors: ' . print_r($userModel->errors(), true));
            return redirect()->back()->withInput()->with('error', 'Registration failed. Please try again.');
        }

        // 5. Registration successful! Redirect to login page (or wherever appropriate)
        // We'll create the login page next. For now, maybe redirect home.
        return redirect()->to('/login') // Assumes a '/login' route exists or will be created
            ->with('success', 'Registration successful! Please log in.');

    }

    /**
     * Display the login form.
     * (To be implemented later)
     */
    public function loginShow()
    {
        return view('login_form');
    }

    /**
     * Process the login form submission.
     * (To be implemented later)
     */
    public function loginAttempt()
    {
        // 1. Define Validation Rules
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

        // 2. Run Validation
        if (!$this->validate($rules)) {
            // Redirect back with validation errors
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        // 3. Validation passed, get credentials
        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        // 4. Find user by email
        $userModel = new UserModel();
        $user = $userModel->where('email', $email)->first();

        // 5. Verify User and Password
        // Check if user exists AND if the submitted password matches the stored hash
        if ($user === null || !password_verify($password, $user['password'])) {
            // Invalid credentials - Redirect back with a generic error
            // Don't reveal whether the email exists or just the password was wrong
            return redirect()->back()->withInput()->with('error', 'Invalid login credentials.');
        }

        // 6. Login Success! Prepare Session Data
        $session = session(); // Get session instance
        $sessionData = [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'isLoggedIn' => true,
        ];

        // 7. Set Session Data
        $session->set($sessionData);

        // 8. Redirect to a logged-in area (e.g., homepage or dashboard/history later)
        // Optional: Add a welcome flash message
        return redirect()->to('/')->with('success', 'Login successful! Welcome back.'); // Redirect to homepage for now

    }

    /**
     * Log the user out.
     * (To be implemented later)
     */
    public function logout()
    {
        echo "Logging out..."; // Placeholder
    }

}