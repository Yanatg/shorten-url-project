<html lang="en">
    <head>
        <title>URL Shortener</title>
    </head>
    <body>
        <div class="main-container">
            <h1>URL Shortener</h1>
            <form action="#" id="form_url">
                <label for="long_url">Enter Long URL:</label>
                <input type="text" name="long_url" id="long_url" placeholder="https://www.example.com/very/long/url/to/shorten" required>
                <button type="submit" id="shroten-btn">Shorten URL</button>
            </form>

            <div id="result-area">
                <p id="short-url"></p>
                <p id="error-message" class="error-message"></p>
            
        </div>
    </body>
</html>