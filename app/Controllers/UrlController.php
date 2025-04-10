<?php

namespace App\Controllers;

use App\Controllers\BaseController;
// We will use these models later:
use App\Models\UrlModel;
// use App\Models\UserModel;

class UrlController extends BaseController
{
    /**
     * Displays the main form for shortening a URL.
     * This will eventually load the 'shorten_form' view.
     */
    public function index()
    {
        // Remove the old echo message
        // echo "UrlController::index() - URL Shortening Form will be here.";

        // Load and return the view file: app/Views/shorten_form.php
        // We can also pass data to the view in an array as the second argument if needed
        return view('shorten_form');
    }

    /**
     * Handles the submission of the new URL form
     * and creates the short URL.
     */
    public function create()
    {
        // 1. Setup Validation Rules
        $validationRules = [
            'original_url' => [
                'label' => 'URL', // User-friendly field name for errors
                'rules' => 'required|valid_url_strict|max_length[2048]',
                'errors' => [
                    'required' => 'Please enter a URL to shorten.',
                    'valid_url_strict' => 'Please enter a valid URL including http:// or https://.',
                    'max_length' => 'The URL you entered is too long.'
                ]
            ]
        ];

        // 2. Run Validation
        if (! $this->validate($validationRules)) {
            // Validation failed, redirect back to the form with errors
            // Pass validation errors and old input data back to the view
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        // 3. Validation passed, get the URL
        $originalUrl = $this->request->getPost('original_url');

        // --- Optional: Check if URL already exists (to avoid duplicates) ---
        $urlModel = new UrlModel();
        $existing = $urlModel->where('original_url', $originalUrl)
                            // Optional: Add ->where('user_id', $userId) if checking per-user
                            ->first();

        if ($existing) {
            // URL already shortened, return existing short URL
            $shortUrl = base_url($existing['short_code']); // base_url() gets your app.baseURL
            return redirect()->back()->withInput()->with('success', 'URL already shortened!')
                                     ->with('short_url', $shortUrl);
        }
        // --- End Optional Check ---


        // 4. Prepare data for insertion (initially without short_code)
        $data = [
            'original_url' => $originalUrl,
            'user_id'      => null, // TODO: Set this later from logged-in user session if implementing auth
            'visit_count'  => 0,
        ];

        // 5. Insert into database to get the ID
        $insertedId = $urlModel->insert($data, true); // true returns the insert ID

        if (! $insertedId) {
            // Database insert failed
            log_message('error', 'Failed to insert URL into database: ' . print_r($urlModel->errors(), true));
            return redirect()->back()->withInput()->with('error', 'Could not save the URL. Please try again later.');
        }

        // 6. Generate short code from the insert ID (using base62)
        $shortCode = $this->encodeBase62($insertedId);

        // 7. Update the record with the generated short code
        if (! $urlModel->update($insertedId, ['short_code' => $shortCode])) {
            // Database update failed - handle this edge case (maybe delete the inserted row?)
            log_message('error', 'Failed to update URL record ID ' . $insertedId . ' with short_code: ' . print_r($urlModel->errors(), true));
            // Consider deleting the record: $urlModel->delete($insertedId);
            return redirect()->back()->withInput()->with('error', 'Could not generate the short URL code. Please try again later.');
        }

        // 8. Generate the full short URL
        $shortUrl = base_url($shortCode); // base_url() gets your app.baseURL + the code

        // 9. Redirect back with success message and the short URL
        // Use flashdata so the message is shown only on the next request
        return redirect()->to('/') // Redirect to the main form page
                         ->with('success', 'URL shortened successfully!')
                         ->with('short_url', $shortUrl);
    }

    /**
     * Encodes an integer ID into a base62 string.
     * Base62 uses characters 0-9, a-z, A-Z.
     *
     * @param int $number The integer ID to encode.
     * @return string The base62 encoded string.
     */
    private function encodeBase62(int $number): string
    {
        // Note: For production, consider moving this to a Helper file (e.g., app/Helpers/encoding_helper.php)
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $base = strlen($chars); // 62
        $encoded = '';

        if ($number === 0) {
            return $chars[0]; // Handle 0 case
        }

        while ($number > 0) {
            $remainder = $number % $base;
            $encoded = $chars[$remainder] . $encoded; // Prepend the character
            $number = intdiv($number, $base); // Integer division
        }

        return $encoded;
    }

    // We'll also need a decodeBase62 later for potential custom code lookups, but not for basic ID->code generation.

    /**
     * Redirects a short code to its original URL.
     * (To be implemented later)
     * @param string|null $shortCode The short code from the URL segment
     */
    public function redirect($shortCode = null)
    {
        // Lookup shortCode in UrlModel, increment count, redirect here
        if (empty($shortCode)) {
            // Maybe redirect to home or show an error
            return redirect()->to('/')->with('error', 'No short code provided.');
        }
        echo "UrlController::redirect() - Redirecting for code: " . esc($shortCode); // esc() is for security
    }

    /**
     * Shows the URL history for the logged-in user.
     * (To be implemented later - requires authentication)
     */
    public function history()
    {
        // Check login status, fetch URLs for user_id from UrlModel, load view here
        echo "UrlController::history() - User URL history page.";
    }

    // We might add other methods later, like 'delete', 'qrcode', etc.
}