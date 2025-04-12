<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UrlModel;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use CodeIgniter\Exceptions\PageNotFoundException;

class UrlController extends BaseController
{
    /**
     * Displays the main form for shortening a URL.
     * Also displays URL history if the user is logged in.
     */
    public function index()
    {
        $session = session();
        $data = [];

        // Check if user is logged in
        if ($session->get('isLoggedIn')) {
            $userId = $session->get('user_id');
            $urlModel = new UrlModel();

            // Fetch URLs created by this user, newest first
            // Load the Text helper if you use character_limiter in the view
            helper('text');
            $data['userUrls'] = $urlModel->where('user_id', $userId)
                ->orderBy('created_at', 'DESC')
                ->findAll();
        }
        
        return view('shorten_form', $data);
    }

    /**
     * Handles the submission of the new URL form
     * and creates the short URL. Checks for duplicates for logged-in users.
     */
    public function create()
    {
        // 1. Setup Validation Rules
        $validationRules = [
            'original_url' => [
                'label' => 'URL',
                'rules' => 'required|valid_url_strict|max_length[2048]',
                'errors' => [ /* ... errors ... */ ]
            ]
        ];

        // 2. Run Validation
        if (! $this->validate($validationRules)) {
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        // 3. Validation passed, get URL and User ID
        $originalUrl = $this->request->getPost('original_url');
        $urlModel = new UrlModel();
        $session = session();
        $userId = $session->get('isLoggedIn') ? $session->get('user_id') : null;

        // --- 4. Check for Duplicates IF User is Logged In ---
        if ($userId !== null) {
            $existing = $urlModel->where('original_url', $originalUrl)
                                 ->where('user_id', $userId) // Check against this specific user
                                 ->select('short_code')     // Only need the short_code
                                 ->first();

            if ($existing) {
                // Match found for this user and this URL!
                log_message('info', "User ID {$userId} submitted duplicate URL. Existing code: {$existing['short_code']}");
                $shortCode = $existing['short_code'];
                $shortUrl = site_url($shortCode); // Or base_url()

                // Redirect back with existing info
                return redirect()->to('/')
                                 ->with('success', 'You have already shortened this URL:') // Use 'success' or a different key like 'info'
                                 ->with('short_url', $shortUrl)
                                 ->with('new_short_code', $shortCode); // Pass code for QR button
            }
        }
        // --- End Duplicate Check ---

        // 5. No duplicate found (or user not logged in), Proceed to Create New Entry
        log_message('info', "Creating new short URL for '{$originalUrl}'" . ($userId ? " by user ID {$userId}" : " anonymously"));

        $data = [
            'original_url' => $originalUrl,
            'user_id'      => $userId,
            'visit_count'  => 0,
        ];

        // 6. Insert into database to get the ID
        $insertedId = $urlModel->insert($data, true);

        if (! $insertedId) {
            log_message('error', 'Failed to insert URL into database: ' . print_r($urlModel->errors(), true));
            return redirect()->back()->withInput()->with('error', 'Could not save the URL. Please try again later.');
        }

        // 7. Generate short code from the insert ID
        $shortCode = $this->encodeBase62($insertedId);
        log_message('debug', "Generated shortCode '{$shortCode}' for ID {$insertedId}");

        // 8. Update the record with the generated short code
        $updateData = ['short_code' => $shortCode];
        $updateSuccess = $urlModel->update($insertedId, $updateData);

        if (!$updateSuccess) {
            log_message('error', "FAILED to update ID {$insertedId} with short_code '{$shortCode}'. Model errors: " . print_r($urlModel->errors(), true));
            // Consider cleanup: $urlModel->delete($insertedId);
            return redirect()->back()->withInput()->with('error', 'Could not generate the short URL code. Please try again later.');
        } else {
             log_message('debug', "Successfully updated ID {$insertedId} with short_code '{$shortCode}'.");
        }

        // 9. Generate the full short URL
        $shortUrl = site_url($shortCode); // Or base_url()

        log_message('debug', "Generated full short URL: {$shortUrl}");

        // 10. Redirect back with success message and the NEW short URL info
        return redirect()->to('/')
                         ->with('success', 'URL shortened successfully!')
                         ->with('short_url', $shortUrl)
                         ->with('new_short_code', $shortCode);
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

        // Validate input
        if (empty($shortCode)) {
            log_message('warning', 'Redirect attempt with empty shortCode.');
            throw PageNotFoundException::forPageNotFound('No short code provided.');
        }

        // Find the URL record in the database
        $urlModel = new UrlModel();
        log_message('debug', "Looking up short_code: '{$shortCode}' in database.");

        $urlRecord = $urlModel->where('short_code', $shortCode)
            ->select('id, original_url, visit_count')
            ->first();

        // Log the result
        if ($urlRecord === null) {
            log_message('debug', "Database lookup FAILED for shortCode: '{$shortCode}'. Result is NULL.");
        } else {
            log_message('debug', "Database lookup SUCCEEDED for shortCode: '{$shortCode}'. Record found: " . print_r($urlRecord, true));
        }

        // Handle Not Found
        if ($urlRecord === null) {
            throw PageNotFoundException::forPageNotFound('Sorry, that short link was not found.');
        }

        // Increment Visit Count (Manual Way)
        $newVisitCount = $urlRecord['visit_count'] + 1;
        $updateData = ['visit_count' => $newVisitCount];

        log_message('debug', "Attempting to update visit count for ID: {$urlRecord['id']} to {$newVisitCount}");

        if (!$urlModel->update($urlRecord['id'], $updateData)) {
            log_message('error', "Failed to update visit count for ID: {$urlRecord['id']}. Model errors: " . print_r($urlModel->errors(), true));
        } else {
            log_message('debug', "Visit count updated successfully for ID: {$urlRecord['id']}.");
        }

        // Perform the Redirect
        log_message('info', "Redirecting short_code '{$shortCode}' to '{$urlRecord['original_url']}'");
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

        $urlModel = new UrlModel();
        if (!$urlModel->where('short_code', $shortCode)->select('id')->first()) {
            throw PageNotFoundException::forPageNotFound('Short code not found for QR code generation.');
        }

        // Construct the full URL that the QR code should point to
        $fullShortUrl = site_url($shortCode);

        log_message('debug', "Attempting basic QR generation for: {$fullShortUrl}");

        try {
            // Create QR Code object with data ONLY
            $qrCode = new QrCode($fullShortUrl);

            // Create writer
            $writer = new PngWriter();

            // Write the basic QR code
            $result = $writer->write($qrCode);

            // Output the result
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
        $session = session();
        if (!$session->get('isLoggedIn')) {
            return redirect()->to('/login')->with('error', 'Authentication required.');
        }

        if (empty($id)) {
            return redirect()->to('/')->with('error', 'Invalid URL ID provided for deletion.');
        }

        $userId = $session->get('user_id');
        $urlModel = new UrlModel();

        $urlRecord = $urlModel->where('id', $id)
            ->where('user_id', $userId)
            ->select('id')
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