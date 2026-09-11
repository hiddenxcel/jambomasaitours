<?php
/**
 * Jambo Masai Tours — AI image matching (Groq text model).
 *
 * The image library holds admin-written captions per photo (no vision model
 * available on this Groq account, so matching is done on text: each day's
 * title/description/destination/accommodation is compared against the
 * library captions and Groq picks the best-fitting photo ids per day.
 */

/** Build the prompt text for matchLibraryImagesForDays() without making the call. */
function buildImageMatchPrompt(array $days, array $library, int $perDay = 2): ?string {
    $library = array_values(array_filter($library, fn($p) => !empty($p['caption'])));
    if (empty($library) || empty($days)) return null;

    $photoLines = [];
    foreach ($library as $p) {
        $photoLines[] = $p['id'] . ': ' . $p['caption'] . (!empty($p['destination']) ? ' (' . $p['destination'] . ')' : '');
    }

    $dayLines = [];
    foreach ($days as $d) {
        $dayLines[] = $d['day_number'] . ': ' . $d['title']
            . ' — ' . mb_substr($d['description'] ?? '', 0, 160)
            . (!empty($d['accommodation']) ? ' | stay: ' . $d['accommodation'] : '');
    }

    return "You are matching safari itinerary days to a photo library based on captions.\n\n"
        . "PHOTO LIBRARY (id: caption):\n" . implode("\n", $photoLines) . "\n\n"
        . "ITINERARY DAYS (day_number: title — description):\n" . implode("\n", $dayLines) . "\n\n"
        . "For each day, pick up to {$perDay} photo ids from the library whose captions best match that day's "
        . "location, activity, or accommodation. Prefer variety — avoid reusing the same photo across many days "
        . "unless it's clearly the best match. Respond with ONLY a JSON object mapping day_number (string) to an "
        . "array of photo ids, no explanation. Example: {\"1\": [3, 7], \"2\": [1]}";
}

/** Parse the decoded JSON result of an image-match prompt into day_number => [photo id, ...]. */
function parseImageMatchResult(?array $parsed, array $library, int $perDay = 2): array {
    if (!is_array($parsed)) return [];
    $validIds = array_column($library, 'id');
    $result = [];
    foreach ($parsed as $dayNum => $ids) {
        if (!is_array($ids)) continue;
        $clean = array_values(array_intersect(array_map('intval', $ids), $validIds));
        if ($clean) $result[(int)$dayNum] = array_slice($clean, 0, $perDay);
    }
    return $result;
}

/**
 * Ask Groq to match itinerary days to library photos by caption text.
 * (Standalone/single-call version — prefer the batched path in
 * generateItineraryPdf() when matching runs alongside the other AI calls.)
 *
 * @param array $days  Each day needs: day_number, title, description, accommodation
 * @param array $library Each photo needs: id, caption, destination
 * @return array<int,int[]> day_number => [library photo id, ...] (empty on failure)
 */
function matchLibraryImagesForDays(array $days, array $library, int $perDay = 2): array {
    $apiKey = getGroqApiKey();
    if (empty($apiKey) || !defined('GROQ_API_URL') || !defined('GROQ_TEXT_MODEL')) return [];
    $prompt = buildImageMatchPrompt($days, $library, $perDay);
    if (!$prompt) return [];

    $payload = [
        'model' => GROQ_TEXT_MODEL,
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'max_tokens' => 500,
        'temperature' => 0.3,
        'response_format' => ['type' => 'json_object'],
    ];

    $ch = curl_init(GROQ_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 15,
    ]);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$resp) {
        error_log('Groq image matching failed: HTTP ' . $httpCode . ' ' . $resp);
        return [];
    }

    $data = json_decode($resp, true);
    $content = $data['choices'][0]['message']['content'] ?? '';
    $parsed = json_decode($content, true);
    return parseImageMatchResult($parsed, array_values(array_filter($library, fn($p) => !empty($p['caption']))), $perDay);
}

/**
 * Normalize a trip's raw departure/arrival location text into short, clean
 * place names for the route-map timeline. Itinerary text is written in plain
 * language by admins (e.g. "Same area (full-day exploration)" instead of
 * repeating "Northern Serengeti"), so a single Groq call sees the whole day
 * sequence at once and can infer what "same area" refers to from context —
 * plain string matching can't do that.
 *
 * @param array $days Each day needs: day_number, departure_location, arrival_location
 * @return array<int,array{departure:string,arrival:string}> day_number => cleaned names (empty on failure — caller should fall back to the raw text)
 */
/** Build the prompt text for cleanRouteLocations() without making the call. */
function buildRouteCleanupPrompt(array $days): ?string {
    $dayLines = [];
    foreach ($days as $d) {
        $dep = trim($d['departure_location'] ?? '');
        $arr = trim($d['arrival_location'] ?? '');
        if ($dep === '' && $arr === '') continue;
        $dayLines[] = $d['day_number'] . ': departs "' . $dep . '", arrives "' . $arr . '"';
    }
    if (empty($dayLines)) return null;

    return "This is a Tanzania safari itinerary's day-by-day departure/arrival locations, written in plain "
        . "language by a tour operator. Some entries are vague (e.g. \"Same area\", \"full-day exploration\") and "
        . "refer back to a location mentioned on an earlier day — use the sequence to resolve them to the actual "
        . "place name. Return a SHORT, clean place name (2-4 words, e.g. \"Northern Serengeti\", \"Karatu\", "
        . "\"Tarangire National Park\") for both departure and arrival of each day — never leave one vague.\n\n"
        . implode("\n", $dayLines) . "\n\n"
        . "Respond with ONLY a JSON object mapping day_number (string) to {\"departure\":\"...\",\"arrival\":\"...\"}, "
        . "no explanation. Example: {\"1\": {\"departure\":\"Arusha\",\"arrival\":\"Tarangire National Park\"}}";
}

/** Parse the decoded JSON result of a route-cleanup prompt into day_number => ['departure'=>..,'arrival'=>..]. */
function parseRouteCleanupResult(?array $parsed): array {
    if (!is_array($parsed)) return [];
    $result = [];
    foreach ($parsed as $dayNum => $loc) {
        if (!is_array($loc)) continue;
        $result[(int)$dayNum] = [
            'departure' => trim($loc['departure'] ?? ''),
            'arrival' => trim($loc['arrival'] ?? ''),
        ];
    }
    return $result;
}

/**
 * Normalize a trip's raw departure/arrival location text into short, clean
 * place names for the route-map timeline. (Standalone/single-call version —
 * prefer the batched path in generateItineraryPdf().)
 *
 * @param array $days Each day needs: day_number, departure_location, arrival_location
 * @return array<int,array{departure:string,arrival:string}> day_number => cleaned names (empty on failure)
 */
function cleanRouteLocations(array $days): array {
    $apiKey = getGroqApiKey();
    if (empty($days) || empty($apiKey) || !defined('GROQ_API_URL') || !defined('GROQ_TEXT_MODEL')) {
        return [];
    }
    $prompt = buildRouteCleanupPrompt($days);
    if (!$prompt) return [];

    $payload = [
        'model' => GROQ_TEXT_MODEL,
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'max_tokens' => 600,
        'temperature' => 0.2,
        'response_format' => ['type' => 'json_object'],
    ];

    $ch = curl_init(GROQ_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 15,
    ]);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$resp) {
        error_log('Groq route location cleanup failed: HTTP ' . $httpCode . ' ' . $resp);
        return [];
    }

    $data = json_decode($resp, true);
    $content = $data['choices'][0]['message']['content'] ?? '';
    return parseRouteCleanupResult(json_decode($content, true));
}
