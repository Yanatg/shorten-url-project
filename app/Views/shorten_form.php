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

<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
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
            <?php ?>
            <?php if (session()->getFlashdata('short_url')): ?>
                <div class="bg-gray-200 p-4 rounded border border-gray-300">
                    <p class="text-gray-700 mb-2">Your Short URL:</p>
                    <?php $shortUrlFull = session()->getFlashdata('short_url'); ?>

                    <a href="<?= $shortUrlFull ?>" target="_blank"
                        class="text-blue-600 font-bold break-all hover:underline">

                        <?php ?>
                        <?= esc($shortUrlFull) ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>