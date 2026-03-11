<?php

namespace App\Infrastructure;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VoiceVoxClient
{
    private string $baseUrl = "http://127.0.0.1:50021";

    public function synthesize(string $text, int $speaker): ?string
    {
        $query = Http::post(
            $this->baseUrl . "/audio_query?text=" . urlencode($text) . "&speaker=" . $speaker
        );

        if (!$query->successful()) {
            Log::error("audio_query error: ".$query->body());
            return null;
        }

        $synthesis = Http::withBody(
            $query->body(),
            'application/json'
        )->post(
            $this->baseUrl . "/synthesis?speaker=".$speaker
        );

        if (!$synthesis->successful()) {
            Log::error("synthesis error: ".$synthesis->body());
            return null;
        }

        return $synthesis->body(); // バイナリ
    }
}