<?php
// xss-test.php
// A SAFE test page to check output escaping
session_start();
header('Content-Type: text/html; charset=UTF-8');

$input = $_POST['test_input'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>XSS Test Page</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .result-box { border: 1px solid #ccc; padding: 10px; margin-top: 15px; }
        .safe { background: #e8fbe8; }
        .unsafe { background: #fbe8e8; }
    </style>
</head>
<body>
    <h2>Safe XSS Test Page</h2>
    <form method="post">
        <label>Enter test input:</label><br>
        <input type="text" name="test_input" value="<?= htmlspecialchars($input, ENT_QUOTES, 'UTF-8') ?>" style="width:300px;">
        <button type="submit">Test</button>
    </form>

    <?php if ($input !== ''): ?>
        <div class="result-box unsafe">
            <strong>Raw output (UNSAFE):</strong><br>
            <?= $input ?>
        </div>

        <div class="result-box safe">
            <strong>Escaped output (SAFE):</strong><br>
            <?= htmlspecialchars($input, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
</body>
</html>
