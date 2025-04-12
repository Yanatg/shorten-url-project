<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Short URL</title>
    <link rel="stylesheet" href="/css/app.css">
    <meta name="current-time" content="<?= date('Y-m-d H:i:s T') ?>">
    <meta name="current-location" content="Bangkok, Thailand">
    <link rel="stylesheet" href="<?= base_url('css/style.css') ?>">
</head>

<body class="bg-gray-100 min-h-screen">
<div class="bg-blue-500 text-white py-2 px-4 shadow-md">
        <div class="gap-4 mx-auto flex justify-end items-center">
             <?php if (session()->get('isLoggedIn')): ?>
                <span class="text-sm mr-4">Welcome, <?= esc(session()->get('email')) ?>!</span>
                <a href="<?= url_to('AuthController::logout') ?>" class="ml-auto text-sm text-gray-300 hover:text-white hover:underline font-semibold">Logout</a>
            <?php else: ?>
                <a href="<?= url_to('AuthController::loginShow') ?>" class="text-sm text-gray-300 hover:text-white hover:underline font-semibold mr-4">Login</a>
                <a href="<?= url_to('AuthController::registerShow') ?>" class="text-sm text-gray-300 hover:text-white hover:underline font-semibold">Register</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="container mx-auto px-4 py-8">

        <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-lg mx-auto mb-10">
            <h1 class="text-2xl font-bold mb-6 text-center text-gray-700">Shorten a Long URL</h1>
            <?php if (session()->getFlashdata('success')): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?= esc(session()->getFlashdata('success')) ?></span>
                </div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?= esc(session()->getFlashdata('error')) ?></span>
                </div>
            <?php endif; ?>
            <?php if (isset($validation)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Oops! Please fix the errors:</strong>
                    <ul class="mt-2 list-disc list-inside">
                        <?php foreach ($validation->getErrors() as $error): ?>
                            <li><?= esc($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <form action="<?= url_to('UrlController::create') ?>" method="post">
                <?= csrf_field() ?>
                <div class="mb-4">
                    <label for="original_url" class="block text-gray-700 text-sm font-bold mb-2">Enter Long URL:</label>
                    <input type="url" name="original_url" id="original_url"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?= isset($validation) && $validation->hasError('original_url') ? 'border-red-500' : '' ?>"
                        placeholder="https://www.example.com/very/long/url/to/shorten"
                        value="<?= old('original_url', '') ?>" required>
                    <?php if (isset($validation) && $validation->hasError('original_url')): ?>
                        <p class="text-red-500 text-xs italic"><?= esc($validation->getError('original_url')) ?></p>
                    <?php endif; ?>
                </div>
                <div class="flex items-center justify-center">
                    <button type="submit"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Shorten URL
                    </button>
                </div>
            </form>
            <div id="result-area" class="mt-6 text-center">
                <?php if (session()->getFlashdata('short_url')): ?>
                    <div class="bg-gray-200 p-4 rounded border border-gray-300">
                        <p class="text-gray-700 mb-2">Your Short URL:</p>
                        <?php $shortUrlFull = session()->getFlashdata('short_url'); ?>
                        <a href="<?= $shortUrlFull ?>" target="_blank"
                            class="text-blue-600 font-bold break-all hover:underline">
                            <?= esc($shortUrlFull) ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div> <div id="history-section" class="w-full max-w-4xl mx-auto">
             <?php if (session()->get('isLoggedIn')): ?>
                <h2 class="text-xl font-semibold mb-4 text-center text-gray-600">Your URL History</h2>
                <?php if (!empty($userUrls)): ?>
                    <div class="overflow-x-auto relative shadow-md sm:rounded-lg bg-white p-4">
                        <table class="w-full text-sm text-left text-gray-500">
                           <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th scope="col" class="py-3 px-6">Original URL</th>
                                    <th scope="col" class="py-3 px-6">Short URL</th>
                                    <th scope="col" class="py-3 px-6">Visits</th>
                                    <th scope="col" class="py-3 px-6">Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userUrls as $url): ?>
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <td class="py-4 px-6 font-medium text-gray-900 whitespace-nowrap max-w-xs overflow-hidden overflow-ellipsis"
                                            title="<?= esc($url['original_url']) ?>">
                                            
                                        </td>
                                        <td class="py-4 px-6">
                                            <?php $shortLink = base_url($url['short_code']); ?>
                                            <a href="<?= $shortLink ?>" target="_blank" class="text-blue-600 hover:underline">
                                                <?= esc($shortLink) ?>
                                            </a>
                                        </td>
                                        <td class="py-4 px-6 text-center">
                                            <?= esc($url['visit_count']) ?>
                                        </td>
                                        <td class="py-4 px-6 whitespace-nowrap">
                                            <?= esc(date('Y-m-d H:i', strtotime($url['created_at']))) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="bg-white p-6 rounded shadow-md text-center text-gray-500 mt-4 max-w-lg mx-auto">
                         <p>You haven't shortened any URLs yet.</p>
                     </div>
                 <?php endif; ?>
             <?php else: // User is not logged in ?>
                 <div class="text-center text-gray-600 p-4 bg-yellow-50 rounded border border-yellow-200 max-w-md mx-auto">
                     <p>Want to track your shortened URLs and view statistics?</p>
                     <p class="mt-2">
                         <a href="<?= url_to('AuthController::loginShow') ?>" class="text-blue-600 hover:underline font-semibold">Login</a>
                         or
                         <a href="<?= url_to('AuthController::registerShow') ?>" class="text-blue-600 hover:underline font-semibold">Create an Account</a>
                     </p>
                 </div>
             <?php endif; ?>
        </div> </div> </body>
</html>