<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;

    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'email',
        'password',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // --- PASSWORD HASHING ---
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['hashPassword'];
    // protected $beforeUpdate   = ['hashPassword']; // Uncomment if you allow password updates

    /**
     * Hashes the password before inserting a user record.
     *
     * @param array $data The data array being inserted.
     * @return array The modified data array with the hashed password.
     */
    protected function hashPassword(array $data): array
    {
        // Check if a password is set in the data being passed to the model
        if (! isset($data['data']['password'])) {
            return $data;
        }

        // Hash the password using PHP's recommended default algorithm
        $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);

        return $data;
    }
}