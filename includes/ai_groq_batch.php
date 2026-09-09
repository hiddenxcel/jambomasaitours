<?php
/**
 * Jambo Masai Tours — run multiple Groq chat-completion calls concurrently.
 *
 * The itinerary PDF makes three independent Groq calls (image matching,
 * route-location cleanup, activity planning). Run sequentially they summed
 * to 60-90s on longer tours, which blew past browser fetch timeouts and
 * surfaced as "Network error" even though the server was still working.
 * curl_multi lets all three requests be in flight at once, so total wait
 * time is close to the slowest single call instead of the sum of all three.
 */

/**
 * @param array<string,array> $requests name => ['prompt'=>string, 'max_tokens'=>int, 'temperature'=>float]
 * @return array<string,?string> name => raw response body (null on failure/timeout for that request)
 */
function runGroqBatch(array $requests): array {
    $apiKey = getGroqApiKey();
    if (empty($requests) || empty($apiKey)) {
        return array_fill_keys(array_keys($requests), null);
    }

    $mh = curl_multi_init();
    $handles = [];

    foreach ($requests as $name => $req) {
        $payload = [
            'model' => GROQ_TEXT_MODEL,
            'messages' => [['role' => 'user', 'content' => $req['prompt']]],
            'max_tokens' => $req['max_tokens'] ?? 800,
            'temperature' => $req['temperature'] ?? 0.3,
            'response_format' => ['type' => 'json_object'],
        ];
        $ch = curl_init(GROQ_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 25,
        ]);
        curl_multi_add_handle($mh, $ch);
        $handles[$name] = $ch;
    }

    $running = null;
    do {
        $status = curl_multi_exec($mh, $running);
        if ($running) curl_multi_select($mh, 1.0);
    } while ($running && $status === CURLM_OK);

    $results = [];
    foreach ($handles as $name => $ch) {
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $body = curl_multi_getcontent($ch);
        if ($httpCode !== 200 || !$body) {
            error_log("Groq batch call '$name' failed: HTTP $httpCode " . $body);
            $results[$name] = null;
        } else {
            $results[$name] = $body;
        }
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);

    return $results;
}

/** Extract and JSON-decode the assistant message content from a raw Groq response body. */
function groqBatchDecode(?string $rawBody): ?array {
    if (!$rawBody) return null;
    $data = json_decode($rawBody, true);
    $content = $data['choices'][0]['message']['content'] ?? '';
    $parsed = json_decode($content, true);
    return is_array($parsed) ? $parsed : null;
}
