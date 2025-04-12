<?php

namespace App\Controllers;

use App\Controllers\BaseController;
// We will use these models later:
use App\Models\UrlModel;
// use App\Models\UserModel;
// Add this line with the other 'use' statements
use CodeIgniter\Exceptions\PageNotFoundException;

class UrlController extends BaseController
{
    /**
     * Displays the main form for shortening a URL.
     * This will eventually load the 'shorten_form' view.
     */
    public function index()
    {
        $session = session();
        $data = []; // Initialize data array to pass to the view

        // Check if user is logged in
        if ($session->get('isLoggedIn')) {
            $userId = $session->get('user_id');
            $urlModel = new UrlModel();

            // Fetch URLs created by this user, newest first
            $data['userUrls'] = $urlModel->where('user_id', $userId)
                ->orderBy('created_at', 'DESC')
                ->findAll(); // Get all records for now
        }
        // If not logged in, $data['userUrls'] will not be set

        // Load the view, passing the fetched data (or empty array)
        return view('shorten_form', $data);
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
        if (!$this->validate($validationRules)) {
            // Validation failed, redirect back to the form with errors
            // Pass validation errors and old input data back to the view
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        // 3. Validation passed, get the URL
        $originalUrl = $this->request->getPost('original_url');
        $urlModel = new UrlModel();

        // --- Optional: Duplicate Check (remains the same) ---
        // ...

        // --- 4. Prepare data for insertion ---
        // Get user ID from session if logged in
        $session = session();
        $userId = $session->get('isLoggedIn') ? $session->get('user_id') : null; // Get ID if logged in, else null

        $data = [
            'original_url' => $originalUrl,
            'user_id' => $userId, // <-- SET USER ID HERE
            'visit_count' => 0,
            // short_code will be added later after insert
        ];
        // --- End Prepare data ---


        // 5. Insert into database to get the ID
        $insertedId = $urlModel->insert($data, true);

        // ... (Rest of the method: error check, encodeBase62, update, redirect - remain the same) ...

        if (!$insertedId) {
            // Database insert failed
            log_message('error', 'Failed to insert URL into database: ' . print_r($urlModel->errors(), true));
            return redirect()->back()->withInput()->with('error', 'Could not save the URL. Please try again later.');
        }

        // 6. Generate short code from the insert ID (using base62)
        $shortCode = $this->encodeBase62($insertedId);

        // 7. Update the record with the generated short code
        if (!$urlModel->update($insertedId, ['short_code' => $shortCode])) {
            // Database update failed
            log_message('error', 'Failed to update URL record ID ' . $insertedId . ' with short_code: ' . print_r($urlModel->errors(), true));
            return redirect()->back()->withInput()->with('error', 'Could not generate the short URL code. Please try again later.');
        }

        // 8. Generate the full short URL
        $shortUrl = base_url($shortCode); // Or site_url($shortCode); if you preferred that

        // 9. Redirect back with success message and the short URL
        return redirect()->to('/')
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
     * Handles redirection from short code to original URL.
     * Increments the visit count.
     *
     * @param string|null $shortCode The short code from the URL segment
     */
    public function redirect($shortCode = null)
    {
        // ... (logging and validation remain the same) ...

        // 2. Find the URL record in the database
        $urlModel = new UrlModel();
        $urlRecord = $urlModel->where('short_code', $shortCode)
            ->select('id, original_url, visit_count') // Ensure visit_count is selected
            ->first();

        // ... (logging for lookup result) ...

        // 3. Handle Not Found
        if ($urlRecord === null) {
            throw PageNotFoundException::forPageNotFound('Sorry, that short link was not found.');
        }

        // --- 4. Increment Visit Count (Manual Way) ---
        $newVisitCount = $urlRecord['visit_count'] + 1;
        $updateData = ['visit_count' => $newVisitCount];

        log_message('debug', "Attempting to update visit count for ID: {$urlRecord['id']} to {$newVisitCount}");

        if (!$urlModel->update($urlRecord['id'], $updateData)) {
            // Log the error if update fails, but maybe don't stop the redirect
            log_message('error', "Failed to update visit count for ID: {$urlRecord['id']}. Model errors: " . print_r($urlModel->errors(), true));
        } else {
            log_message('debug', "Visit count updated successfully for ID: {$urlRecord['id']}.");
        }
        // --- End Manual Increment ---


        // 5. Perform the Redirect
        log_message('info', "Redirecting short_code '{$shortCode}' to '{$urlRecord['original_url']}'");
        return redirect()->to($urlRecord['original_url'], 301);
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