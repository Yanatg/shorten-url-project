<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UrlModel;
// use App\Models\UserModel; // Not directly used in this controller currently

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel; // Base Enum/Class
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode; // Base Enum/Class
use Endroid\QrCode\Writer\PngWriter;
use CodeIgniter\Exceptions\PageNotFoundException; // For errors
// Added for throwing 404 errors

class UrlController extends BaseController
{
    /**
     * Displays the main form for shortening a URL.
     * Also displays URL history if the user is logged in.
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
            // Load the Text helper if you use character_limiter in the view
            helper('text');
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
                'label' => 'URL',
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
            // Validation failed
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        // 3. Validation passed, get the URL
        $originalUrl = $this->request->getPost('original_url');
        $urlModel = new UrlModel();

        // --- Optional: Duplicate Check ---
        // Consider adding duplicate check logic here if desired
        // --- End Optional Check ---

        // 4. Prepare data for insertion
        $session = session();
        $userId = $session->get('isLoggedIn') ? $session->get('user_id') : null;

        $data = [
            'original_url' => $originalUrl,
            'user_id' => $userId, // Save user ID if logged in
            'visit_count' => 0,
            // short_code will be added later
        ];

        // 5. Insert into database to get the ID
        $insertedId = $urlModel->insert($data, true);

        if (!$insertedId) {
            log_message('error', 'Failed to insert URL into database: ' . print_r($urlModel->errors(), true));
            return redirect()->back()->withInput()->with('error', 'Could not save the URL. Please try again later.');
        }

        // 6. Generate short code from the insert ID (using base62)
        $shortCode = $this->encodeBase62($insertedId);
        log_message('debug', "Generated shortCode '{$shortCode}' for ID {$insertedId}");

        // 7. Update the record with the generated short code
        $updateData = ['short_code' => $shortCode];
        $updateSuccess = $urlModel->update($insertedId, $updateData);

        if (!$updateSuccess) {
            log_message('error', "FAILED to update ID {$insertedId} with short_code '{$shortCode}'. Model errors: " . print_r($urlModel->errors(), true));
            // Consider deleting the record if update fails: $urlModel->delete($insertedId);
            return redirect()->back()->withInput()->with('error', 'Could not generate the short URL code. Please try again later.');
        } else {
            log_message('debug', "Successfully updated ID {$insertedId} with short_code '{$shortCode}'.");
        }

        // 8. Generate the full short URL
        $shortUrl = site_url($shortCode); // Or base_url()
        // Note: $shortCode variable holds just the code, e.g., 'j'

        log_message('debug', "Generated full short URL: {$shortUrl}");

        // 9. Redirect back with success message, full URL, AND the short code itself
        return redirect()->to('/')
            ->with('success', 'URL shortened successfully!')
            ->with('short_url', $shortUrl)         // For display/linking
            ->with('new_short_code', $shortCode); // Pass the code for the QR button
    }

    /**
     * Handles redirection from short code to original URL.
     * Increments the visit count.
     *
     * @param string|null $shortCode The short code from the URL segment
     */
    public function redirect($shortCode = null)
    {
        log_message('debug', "--- Redirect method started. Received shortCode: '{$shortCode}' ---");

        // 1. Validate input
        if (empty($shortCode)) {
            log_message('warning', 'Redirect attempt with empty shortCode.');
            throw PageNotFoundException::forPageNotFound('No short code provided.');
        }

        // 2. Find the URL record in the database
        $urlModel = new UrlModel();
        log_message('debug', "Looking up short_code: '{$shortCode}' in database.");

        $urlRecord = $urlModel->where('short_code', $shortCode)
            ->select('id, original_url, visit_count') // Select only needed fields
            ->first();

        // Log the result
        if ($urlRecord === null) {
            log_message('debug', "Database lookup FAILED for shortCode: '{$shortCode}'. Result is NULL.");
        } else {
            log_message('debug', "Database lookup SUCCEEDED for shortCode: '{$shortCode}'. Record found: " . print_r($urlRecord, true));
        }

        // 3. Handle Not Found
        if ($urlRecord === null) {
            throw PageNotFoundException::forPageNotFound('Sorry, that short link was not found.');
        }

        // 4. Increment Visit Count (Manual Way)
        $newVisitCount = $urlRecord['visit_count'] + 1;
        $updateData = ['visit_count' => $newVisitCount];

        log_message('debug', "Attempting to update visit count for ID: {$urlRecord['id']} to {$newVisitCount}");

        if (!$urlModel->update($urlRecord['id'], $updateData)) {
            log_message('error', "Failed to update visit count for ID: {$urlRecord['id']}. Model errors: " . print_r($urlModel->errors(), true));
            // Decide if you want to stop redirect on count failure, usually not.
        } else {
            log_message('debug', "Visit count updated successfully for ID: {$urlRecord['id']}.");
        }

        // 5. Perform the Redirect
        log_message('info', "Redirecting short_code '{$shortCode}' to '{$urlRecord['original_url']}'");
        // Use a 301 redirect (Moved Permanently)
        return redirect()->to($urlRecord['original_url'], 301);
    }

    /**
     * Generates and outputs a QR code image for a given short code.
     * (Simplified working version for endroid/qr-code v6+)
     *
     * @param string|null $shortCode
     */
    public function qrcode($shortCode = null)
    {
        if (empty($shortCode)) {
            throw PageNotFoundException::forPageNotFound('No short code provided for QR code.');
        }

        // Optional: Verify the shortCode actually exists
        $urlModel = new UrlModel();
        if (!$urlModel->where('short_code', $shortCode)->select('id')->first()) {
            throw PageNotFoundException::forPageNotFound('Short code not found for QR code generation.');
        }

        // Construct the full URL that the QR code should point to
        $fullShortUrl = site_url($shortCode); // Or base_url($shortCode)

        log_message('debug', "Attempting basic QR generation for: {$fullShortUrl}");

        try {
            // 1. Create QR Code object with data ONLY
            $qrCode = new QrCode($fullShortUrl);

            // 2. Create writer
            $writer = new PngWriter();

            // 3. Write the basic QR code (No extra configuration options)
            $result = $writer->write($qrCode);

            // 4. Output the result
            $this->response->setHeader('Content-Type', $result->getMimeType());
            $this->response->setBody($result->getString());
            return $this->response;

        } catch (\Exception $e) {
            log_message('error', '[QR Code Simple Error] ' . $e->getMessage());
            throw PageNotFoundException::forPageNotFound('Could not generate basic QR code.');
        }
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
        // For production, consider moving this to a Helper
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $base = strlen($chars);
        $encoded = '';

        if ($number === 0) {
            return $chars[0];
        }

        while ($number > 0) {
            $remainder = $number % $base;
            $encoded = $chars[$remainder] . $encoded;
            $number = intdiv($number, $base);
        }

        return $encoded;
    }

    /**
     * Handles deleting a specific URL entry.
     * Ensures the URL belongs to the logged-in user.
     *
     * @param int $id The ID of the URL record to delete
     */
    public function delete($id = null)
    {
        // Double check login status (though filter should handle it)
        $session = session();
        if (!$session->get('isLoggedIn')) {
            return redirect()->to('/login')->with('error', 'Authentication required.');
        }

        // Validate ID
        if (empty($id)) {
            return redirect()->to('/')->with('error', 'Invalid URL ID provided for deletion.');
        }

        $userId = $session->get('user_id');
        $urlModel = new UrlModel();

        // Find the URL record *and* verify it belongs to the current user
        $urlRecord = $urlModel->where('id', $id)
            ->where('user_id', $userId) // CRITICAL: Ensure ownership
            ->select('id') // Only need ID for deletion check
            ->first();

        // Check if record exists and belongs to user
        if ($urlRecord === null) {
            log_message('warning', "Delete attempt failed: URL ID {$id} not found or does not belong to user ID {$userId}.");
            return redirect()->to('/')->with('error', 'URL not found or you do not have permission to delete it.');
        }

        // Attempt to delete the record
        if ($urlModel->delete($id)) {
            log_message('info', "User ID {$userId} successfully deleted URL ID {$id}.");
            return redirect()->to('/')->with('success', 'URL entry deleted successfully.');
        } else {
            log_message('error', "Failed to delete URL ID {$id} for user ID {$userId}. Model Errors: " . print_r($urlModel->errors(), true));
            return redirect()->to('/')->with('error', 'Failed to delete the URL entry. Please try again.');
        }
    }

}