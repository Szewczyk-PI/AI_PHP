<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        .response {
            white-space: pre-wrap; /* Umożliwia zawijanie tekstu */
            word-wrap: break-word; /* Zawijanie wyrazów */
            background-color: #f0f0f0; /* Jasne tło dla czytelności */
            padding: 10px; /* Odsunięcie tekstu od krawędzi */
            border: 1px solid #ccc; /* Delikatna ramka */
            border-radius: 5px; /* Zaokrąglone rogi */
            max-width: 100%; /* Maksymalna szerokość kontenera */
        }
    </style>
</head>
<body>
    <form action="ai.php" method="post" enctype="multipart/form-data">
        <label>Podaj link oferty: </label>
        <input type="text" name="linki" required><br><br>
        <label>Dodaj plik tekstowy: </label>
        <input type="file" name="file" accept=".txt" required><br><br>
        <input type="submit" value="Wyślij">
    </form>
</body>
</html>
<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $linki = $_POST["linki"];
    
    // Sprawdzenie, czy podano prawidłowy URL
    if (filter_var($linki, FILTER_VALIDATE_URL)) {
        echo "Pobrany link: " . htmlspecialchars($linki) . "<br><br>";

        // Pobranie zawartości strony
        $html = file_get_contents($linki);
        if ($html === FALSE) {
            echo "Nie udało się pobrać zawartości strony.";
            exit;
        }

        // Przypisanie pobranej zawartości strony do zmiennej
        $textContent = strip_tags($html);

        // Ograniczenie długości tekstu do 2048 znaków
        $maxLength = 2048;
        if (strlen($textContent) > $maxLength) {
            $textContent = substr($textContent, 0, $maxLength);
        }

        // Sprawdzenie, czy plik został przesłany
        if (isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['file']['tmp_name'];
            $fileName = $_FILES['file']['name'];
            $fileType = $_FILES['file']['type'];

            // Odczyt zawartości pliku
            $fileContent = file_get_contents($fileTmpPath);

            // Przygotowanie treści wiadomości dla API
            $messageContent = "Zawartość wymagań oferty:\n$textContent\n\nMoje CV:\n$fileContent\n\nEdytuj moje CV tak by jak najbardziej pasowało do oferty, Dopisz coś z sensem w umiejętnościach. Musi być po polsku.";

            // Przygotowanie danych do API
            $apiKey = '';  // Podstaw swój klucz API
            $apiUrl = 'https://api.groq.com/openai/v1/chat/completions';
            $data = [
                'messages' => [
                    ['role' => 'user', 'content' => $messageContent]
                ],
                'model' => 'llama3-70b-8192',
                'temperature' => 1.5,
                'max_tokens' => 2048,
                'top_p' => 1.0,
                'stream' => false,
                'stop' => null
            ];

            // Konwersja danych do formatu JSON
            $dataJson = json_encode($data);

            // Inicjalizacja cURL
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataJson);

            // Wykonanie zapytania cURL
            $response = curl_exec($ch);

            // Sprawdzanie błędów cURL
            if (curl_errno($ch)) {
                echo 'cURL error: ' . curl_error($ch);
                exit;
            }

            curl_close($ch);

            // Przetwarzanie odpowiedzi z API
            $responseData = json_decode($response, true);

            // Usunięcie znaków specjalnych z odpowiedzi
            if (isset($responseData['choices'][0]['message']['content'])) {
                $cleanResponse = $responseData['choices'][0]['message']['content'];
                $cleanResponse = str_replace(["\\n", "\\r", "\\t"], ' ', $cleanResponse);

                // Zamiana + na spacje i * na nowe linie
                $cleanResponse = str_replace('+', ' ', $cleanResponse);
                $cleanResponse = str_replace('*', "\n", $cleanResponse);

                // Zamiana wielokrotnych nowych linii na pojedyncze nowe linie
                $cleanResponse = preg_replace('/\n+/', "\n", $cleanResponse);
            } else {
                $cleanResponse = 'Brak odpowiedzi od modelu.';
            }

            // Wyświetlenie odpowiedzi z API z użyciem klasy CSS
            echo "Odpowiedź z API:<br>";
            echo '<div class="response">' . nl2br(htmlspecialchars($cleanResponse)) . '</div>';
        } else {
            echo "Błąd przy przesyłaniu pliku.";
        }
    } else {
        echo "Nieprawidłowy URL.";
    }
} else {
    echo "Brak danych POST.";
}
?>
