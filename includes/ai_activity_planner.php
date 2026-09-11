<?php
/**
 * Jambo Masai Tours — AI activity scheduling (Groq text model).
 *
 * Itinerary days are stored as one free-text description written by the
 * admin, not a structured time-of-day schedule. This splits that
 * description into Morning/Afternoon/Late afternoon/Evening activity
 * bullets for the PDF's day pages, in the style of a detailed tour
 * proposal — without requiring admins to re-enter every day's plan into
 * new structured fields.
 */

/** Build the prompt text for planDayActivities() without making the call. */
function buildActivityPlanPrompt(array $days): ?string {
    if (empty($days)) return null;

    $dayLines = [];
    foreach ($days as $d) {
        $route = trim(($d['departure_location'] ?? '') . ' to ' . ($d['arrival_location'] ?? ''));
        $dayLines[] = $d['day_number'] . ' (' . $route . '): ' . mb_substr($d['description'] ?? '', 0, 500);
    }

    return "This is a Tanzania safari itinerary written as narrative paragraphs per day. Break each day's "
        . "description into a realistic time-of-day activity schedule using ONLY facts stated or clearly implied "
        . "in that day's own text — do not invent activities, times, or details not present in the source. Use "
        . "these time blocks as needed (skip any that don't apply): \"Morning\", \"Afternoon\", \"Late afternoon\", "
        . "\"Evening\". Each block should have 1-2 short bullet points (under 15 words each), written in second "
        . "person (\"Transfer to...\", \"Enjoy a game drive...\").\n\n"
        . implode("\n\n", $dayLines) . "\n\n"
        . "Respond with ONLY a JSON object mapping day_number (string) to an object of time-block => array of "
        . "bullet strings, no explanation. Example: {\"1\": {\"Morning\": [\"Transfer from Arusha to Tarangire\"], "
        . "\"Afternoon\": [\"Guided game drive in Tarangire National Park\"]}}";
}

/** Parse the decoded JSON result of an activity-plan prompt into day_number => ['Morning'=>[...], ...]. */
function parseActivityPlanResult(?array $parsed): array {
    if (!is_array($parsed)) return [];
    $validBlocks = ['Morning', 'Afternoon', 'Late afternoon', 'Evening'];
    $result = [];
    foreach ($parsed as $dayNum => $blocks) {
        if (!is_array($blocks)) continue;
        $clean = [];
        foreach ($blocks as $blockName => $bullets) {
            if (!in_array($blockName, $validBlocks, true) || !is_array($bullets)) continue;
            $bulletStrings = array_values(array_filter(array_map('strval', $bullets)));
            if ($bulletStrings) $clean[$blockName] = $bulletStrings;
        }
        if ($clean) $result[(int)$dayNum] = $clean;
    }
    return $result;
}

/**
 * Ask Groq to break each day's narrative description into a time-of-day
 * activity schedule, using the day's route (departure/arrival) as context.
 * (Standalone/single-call version — prefer the batched path in
 * generateItineraryPdf().)
 *
 * @param array $days Each day needs: day_number, title, description,
 *                     departure_location, arrival_location
 * @return array<int,array<string,string[]>> day_number => ['Morning'=>[...], 'Afternoon'=>[...], ...] (empty on failure)
 */
function planDayActivities(array $days): array {
    $apiKey = getGroqApiKey();
    if (empty($days) || empty($apiKey) || !defined('GROQ_API_URL') || !defined('GROQ_TEXT_MODEL')) {
        return [];
    }
    $prompt = buildActivityPlanPrompt($days);
    if (!$prompt) return [];

    $payload = [
        'model' => GROQ_TEXT_MODEL,
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'max_tokens' => 1500,
        'temperature' => 0.3,
        'response_format' => ['type' => 'json_object'],
    ];

    $ch = curl_init(GROQ_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 20,
    ]);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$resp) {
        error_log('Groq activity planning failed: HTTP ' . $httpCode . ' ' . $resp);
        return [];
    }

    $data = json_decode($resp, true);
    $content = $data['choices'][0]['message']['content'] ?? '';
    return parseActivityPlanResult(json_decode($content, true));
}
