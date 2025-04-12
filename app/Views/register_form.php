<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register New Account</title>
    <link rel="stylesheet" href="<?= base_url('css/style.css') ?>">
</head>

<body class="bg-rose-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
        <h1 class="text-2xl font-bold mb-6 text-center text-gray-700">Create Account</h1>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <?= esc(session()->getFlashdata('error')) ?>
            </div>
        <?php endif; ?>

        <?php
            // Still need to get the errors array for field-specific checks
            $validationErrors = session()->getFlashdata('validation_errors');
        ?>

        <form action="<?= url_to('AuthController::registerAttempt') ?>" method="post">
            <?= csrf_field() ?>

            <div class="mb-4">
                <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email:</label>
                <input type="email" name="email" id="email"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?= isset($validationErrors['email']) ? 'border-red-500' : '' ?>"
                    value="<?= old('email', '') ?>" required>
                <?php if (isset($validationErrors['email'])): ?>
                    <p class="text-red-500 text-xs italic"><?= esc($validationErrors['email']) ?></p>
                <?php endif; ?>
            </div>

            <div class="mb-4">
                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password:</label>
                 <input type="password" name="password" id="password"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline <?= isset($validationErrors['password']) ? 'border-red-500' : '' ?>"
                    required>
                 <?php if (isset($validationErrors['password'])): ?>
                    <p class="text-red-500 text-xs italic"><?= esc($validationErrors['password']) ?></p>
                <?php endif; ?>
            </div>

            <div class="mb-6">
                <label for="password_confirm" class="block text-gray-700 text-sm font-bold mb-2">Confirm Password:</label>
                <input type="password" name="password_confirm" id="password_confirm"
                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline <?= isset($validationErrors['password_confirm']) ? 'border-red-500' : '' ?>"
                    required>
                 <?php if (isset($validationErrors['password_confirm'])): ?>
                    <p class="text-red-500 text-xs italic"><?= esc($validationErrors['password_confirm']) ?></p>
                <?php endif; ?>
            </div>

            <div class="flex items-center justify-between">
                 <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Register</button>
                 <a href="<?= url_to('AuthController::loginShow') ?? '#' ?>" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">Already have an account? Login</a>
             </div>
        </form>
    </div>
</body>
</html>