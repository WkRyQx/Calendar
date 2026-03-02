<?php

// Az upload könyvtár létrehozása, ha még nem létezik
$uploadDir = __DIR__ . '/upload/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$message = '';
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {
    if (
        isset($_FILES['feltoltott']) && is_uploaded_file($_FILES['feltoltott']['tmp_name'])
    ) {
        $filename = basename($_FILES['feltoltott']['name']);
        // csak képeket engedünk: getimagesize visszaad adatot, ha valóban kép
        $check = getimagesize($_FILES['feltoltott']['tmp_name']);
        if ($check !== false) {
            $targetFile = $uploadDir . $filename;
            if (!file_exists($targetFile)) {
                if (move_uploaded_file($_FILES['feltoltott']['tmp_name'], $targetFile)) {
                    $message = "Sikeres feltöltés: $filename";
                } else {
                    $message = "A fájl másolása nem sikerült.";
                }
            } else {
                $message = "A fájl már létezik a szerveren.";
            }
        } else {
            $message = "Csak képfájlok engedélyezettek.";
        }
    } else {
        $message = "Nem érkezett feltöltött fájl POST metódussal.";
    }
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fájl feltöltése</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 1rem;
            background: #fafafa;
            color: #333;
        }
        .container {
            max-width: 400px;
            margin: 0 auto;
            background: #fff;
            padding: 1rem;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 4px;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        input[type="file"] {
            padding: 0.5rem;
        }
        input[type="submit"] {
            padding: 0.75rem;
            background-color: #007bff;
            border: none;
            color: #fff;
            cursor: pointer;
            border-radius: 4px;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .message {
            margin-bottom: 1rem;
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: #f8f9fa;
        }
        @media (max-width: 600px) {
            .container { padding: 0 1rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data">
            <input type="file" name="feltoltott" accept="image/*">
            <input type="submit" value="Feltöltés indítása">
        </form>
    </div>
</body>
</html>
