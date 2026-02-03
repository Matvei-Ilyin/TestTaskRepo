<?php
header('Content-Type: application/json');
function sendGetRequest($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function getBookData($query, $apiKey) {
    $encodedQuery = urlencode($query);
    $apiUrl = "https://www.googleapis.com/books/v1/volumes?q=" . $encodedQuery;

    if (!empty($apiKey)) {
        $apiUrl .= "&key=" . $apiKey;
    }

    $response = sendGetRequest($apiUrl);
    return json_decode($response, true);
}

$searchQuery = "do androids dream";
$apiKey = '';//PUT YOUR API KEY HERE PRETTY PLEASE. I DONT WANT TO HAVE IT ON PUBLIC GIT REPOSITORY :((((

$bookResults = getBookData($searchQuery, $apiKey);

$simplifiedBooks = [];

if (isset($bookResults['items']) && count($bookResults['items']) > 0) {
    foreach ($bookResults['items'] as $item) {
        $volumeInfo = $item['volumeInfo'];

        $title = $volumeInfo['title'] ?? 'N/A';
        $description = $volumeInfo['description'] ?? 'No description available';
        $authors = $volumeInfo['authors'] ?? ['N/A'];

        $bookDetails = [
            'name' => $title,
            'description' => $description,
            'author' => implode(', ', $authors)
        ];

        $simplifiedBooks[] = $bookDetails;
    }
} else {
    echo "No books found or an error occurred.";
}

echo json_encode($simplifiedBooks, JSON_PRETTY_PRINT);
?>