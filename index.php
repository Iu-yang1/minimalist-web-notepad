<?php

// Path to the directory to save the notes in, without trailing slash.
// Should be outside the document root, if possible.
$save_path = '_tmp';

// Disable caching.
header('Cache-Control: no-store');

// If no note name is provided, or if the name is too long, or if it contains invalid characters.
if (!isset($_GET['note']) || strlen($_GET['note']) > 64 || !preg_match('/^[a-zA-Z0-9_-]+$/', $_GET['note'])) {

    // Generate a name with 5 random unambiguous characters. Redirect to it.
    header("Location: " . substr(str_shuffle('234579abcdefghjkmnpqrstwxyz'), -5));
    die;
}

$note_name = $_GET['note']; // Store for easier access
$path = $save_path . '/' . $note_name;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ensure the directory exists
    if (!is_dir($save_path)) {
        mkdir($save_path, 0777, true); // Create the directory if it doesn't exist
    }

    $text = isset($_POST['text']) ? $_POST['text'] : file_get_contents("php://input");
    // Update file.
    file_put_contents($path, $text);

    // If provided input is empty, delete file.
    if (!strlen($text)) {
        if (is_file($path)) { // Check if file exists before trying to delete
            unlink($path);
        }
    }
    die;
}

// Print raw file when explicitly requested, or if the client is curl or wget.
if (isset($_GET['raw']) || strpos($_SERVER['HTTP_USER_AGENT'], 'curl') === 0 || strpos($_SERVER['HTTP_USER_AGENT'], 'Wget') === 0) {
    if (is_file($path)) {
        header('Content-type: text/plain');
        readfile($path);
    } else {
        header('HTTP/1.0 404 Not Found');
    }
    die;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php print htmlspecialchars($note_name, ENT_QUOTES, 'UTF-8'); ?></title>
<link rel="icon" href="favicon.ico" sizes="any">
<link rel="icon" href="favicon.svg" type="image/svg+xml">
<style>
body {
    margin: 0;
    background: #ebeef1;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}
#status-bar {
    padding: 8px 20px;
    background-color: #f0f0f0;
    border-bottom: 1px solid #ddd;
    font-size: 0.9em;
    color: #555;
    position: sticky;
    top: 0;
    z-index: 1000;
    display: flex; /* Use flexbox for alignment */
    justify-content: space-between; /* Pushes title to left, status to right */
    align-items: center; /* Vertically align items */
}
#status-bar .note-title {
    /* Styles for the note title if needed, e.g., font-weight */
    font-weight: bold;
}
#status-bar .save-status {
    /* Styles for the save status if needed */
}
.container {
    padding: 20px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}
#content {
    margin: 0;
    padding: 20px;
    overflow-y: auto;
    resize: none;
    width: 100%;
    height: 100%;
    box-sizing: border-box;
    border: 1px solid #ddd;
    outline: none;
    flex-grow: 1;
}
#printable {
    display: none;
}
footer {
    padding: 10px 20px;
    background-color: #f0f0f0;
    border-top: 1px solid #ddd;
    text-align: center;
    font-size: 0.8em;
    color: #777;
}
footer a {
    color: inherit; /* Make link color same as footer text */
    text-decoration: none; /* Remove underline */
}
footer a:hover {
    text-decoration: underline; /* Add underline on hover */
}
@media (prefers-color-scheme: dark) {
    body {
        background: #333b4d;
    }
    #status-bar {
        background-color: #2a2e35;
        color: #ccc;
        border-bottom-color: #495265;
    }
    #content {
        background: #24262b;
        color: #fff;
        border-color: #495265;
    }
    footer {
        background-color: #2a2e35;
        color: #aaa;
        border-top-color: #495265;
    }
    /* footer a will inherit dark mode color from footer */
}
@media print {
    #status-bar, .container, footer {
        display: none;
    }
    #printable {
        display: block;
        white-space: pre-wrap;
        word-break: break-word;
    }
}
</style>
</head>
<body>

<div id="status-bar">
    <span class="note-title">Note: <?php print htmlspecialchars($note_name, ENT_QUOTES, 'UTF-8'); ?></span>
    <span class="save-status">Saved</span>
</div>

<div class="container">
<textarea id="content"><?php
if (is_file($path)) {
    print htmlspecialchars(file_get_contents($path), ENT_QUOTES, 'UTF-8');
}
?></textarea>
</div>

<pre id="printable"></pre>

<footer>
    <a href="https://github.com/Iu-yang1/minimalist-web-notepad" target="_blank" rel="noopener noreferrer"><?php echo date("Y"); ?> iu_yang1's Notepad.</a>
    <!-- Replace with your desired URL, or use # if no specific link -->
</footer>

<script>
function uploadContent() {
    if (content !== textarea.value) {
        var temp = textarea.value;
        var request = new XMLHttpRequest();
        request.open('POST', window.location.href, true);
        request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
        
        saveStatusElement.textContent = 'Saving...'; // Update status

        request.onload = function() {
            if (request.readyState === 4) {
                content = temp;
                saveStatusElement.textContent = 'Saved'; // Update status
                setTimeout(uploadContent, 1000);
            }
        }
        request.onerror = function() {
            saveStatusElement.textContent = 'Error saving. Retrying...'; // Update status
            setTimeout(uploadContent, 1000);
        }
        request.send('text=' + encodeURIComponent(temp));

        if (printable.firstChild) {
            printable.removeChild(printable.firstChild);
        }
        printable.appendChild(document.createTextNode(temp));
    }
    else {
        setTimeout(uploadContent, 1000);
    }
}

var textarea = document.getElementById('content');
var printable = document.getElementById('printable');
var saveStatusElement = document.querySelector('#status-bar .save-status'); // Get save status span
var content = textarea.value;

if (content) {
    printable.appendChild(document.createTextNode(content));
} else {
    printable.appendChild(document.createTextNode(''));
}

textarea.addEventListener('input', function() {
    if (textarea.value !== content && saveStatusElement.textContent === 'Saved') {
        saveStatusElement.textContent = 'Unsaved changes';
    }
});

textarea.focus();
uploadContent();
</script>
</body>
</html>