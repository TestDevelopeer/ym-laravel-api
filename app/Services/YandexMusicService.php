<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Ratchet\Client\WebSocket;
use React\EventLoop\Factory;
use React\Promise\Promise;

class YandexMusicService
{
    private string $yaToken;

    public function setToken($token): void
    {
        $this->yaToken = $token;
    }

    public function getCurrentTrackBeta(): JsonResponse|array
    {
        $yaToken = $this->yaToken;
        $deviceId = Str::lower(Str::random(16));
        $deviceInfo = [
            "app_name" => "Chrome",
            "type" => 1,
        ];

        $wsProto = [
            "Ynison-Device-Id" => $deviceId,
            "Ynison-Device-Info" => json_encode($deviceInfo),
        ];

        // First WebSocket connection to get redirect ticket
        $redirectData = $this->connectWebSocket(
            "wss://ynison.music.yandex.ru/redirector.YnisonRedirectService/GetRedirectToYnison",
            [
                "Sec-WebSocket-Protocol" => "Bearer, v2, " . json_encode($wsProto),
                "Origin" => "http://music.yandex.ru",
                "Authorization" => "OAuth " . $yaToken,
            ]
        );

        $redirectData = json_decode($redirectData, true);
        if (!isset($redirectData['redirect_ticket'])) {
            return response()->json(['error' => 'Failed to get redirect ticket'], 500);
        }

        // Prepare data for the second WebSocket connection
        $newWsProto = $wsProto;
        $newWsProto["Ynison-Redirect-Ticket"] = $redirectData['redirect_ticket'];

        $toSend = [
            "update_full_state" => [
                "player_state" => [
                    "player_queue" => [
                        "current_playable_index" => -1,
                        "entity_id" => "",
                        "entity_type" => "VARIOUS",
                        "playable_list" => [],
                        "options" => ["repeat_mode" => "NONE"],
                        "entity_context" => "BASED_ON_ENTITY_BY_DEFAULT",
                        "version" => [
                            "device_id" => $wsProto["Ynison-Device-Id"],
                            "version" => 9021243204784341000,
                            "timestamp_ms" => 0,
                        ],
                        "from_optional" => "",
                    ],
                    "status" => [
                        "duration_ms" => 0,
                        "paused" => true,
                        "playback_speed" => 1,
                        "progress_ms" => 0,
                        "version" => [
                            "device_id" => $wsProto["Ynison-Device-Id"],
                            "version" => 8321822175199937000,
                            "timestamp_ms" => 0,
                        ],
                    ],
                ],
                "device" => [
                    "capabilities" => [
                        "can_be_player" => true,
                        "can_be_remote_controller" => false,
                        "volume_granularity" => 16,
                    ],
                    "info" => [
                        "device_id" => $wsProto["Ynison-Device-Id"],
                        "type" => "WEB",
                        "title" => "Chrome Browser",
                        "app_name" => "Chrome",
                    ],
                    "volume_info" => ["volume" => 0],
                    "is_shadow" => true,
                ],
                "is_currently_active" => false,
            ],
            "rid" => "ac281c26-a047-4419-ad00-e4fbfda1cba3",
            "player_action_timestamp_ms" => 0,
            "activity_interception_type" => "DO_NOT_INTERCEPT_BY_DEFAULT",
        ];

        // Second WebSocket connection to get track data
        $ynisonData = $this->connectWebSocket(
            "wss://" . $redirectData['host'] . "/ynison_state.YnisonStateService/PutYnisonState",
            [
                "Sec-WebSocket-Protocol" => "Bearer, v2, " . json_encode($newWsProto),
                "Origin" => "http://music.yandex.ru",
                "Authorization" => "OAuth " . $yaToken,
            ],
            json_encode($toSend)
        );

        $ynisonData = json_decode($ynisonData, true);
        if (!isset($ynisonData['player_state'])) {
            return response()->json(['error' => 'Failed to fetch track data'], 500);
        }
        $trackIndex = $ynisonData['player_state']['player_queue']['current_playable_index'];
        $trackId = $ynisonData['player_state']['player_queue']['playable_list'][$trackIndex]['playable_id'];

        // Fetch track details (you need to implement this)
        $trackInfo = $this->getTrackInfo($yaToken, $trackId);

        return [
            "ynisonData" => $ynisonData,
            "paused" => $ynisonData['player_state']['status']['paused'],
            "duration_ms" => $ynisonData['player_state']['status']['duration_ms'],
            "progress_ms" => $ynisonData['player_state']['status']['progress_ms'],
            "entity_id" => $ynisonData['player_state']['player_queue']['entity_id'],
            "entity_type" => $ynisonData['player_state']['player_queue']['entity_type'],
            "track" => $trackInfo,
        ];
    }

    /**
     * Helper method to connect to WebSocket and send/receive data.
     */
    private function connectWebSocket(string $url, array $headers, ?string $message = null): string
    {
        $loop = Factory::create();
        $connector = new \Ratchet\Client\Connector($loop);

        $promise = new Promise(function ($resolve, $reject) use ($connector, $url, $headers, $message) {
            $connector($url, [], $headers)
                ->then(function (WebSocket $conn) use ($resolve, $message) {
                    if ($message) {
                        $conn->send($message);
                    }
                    $conn->on('message', function ($msg) use ($resolve, $conn) {
                        $resolve($msg);
                        $conn->close();
                    });
                }, function ($e) use ($reject) {
                    $reject("Could not connect: {$e->getMessage()}");
                });
        });

        $result = null;
        $promise->then(
            function ($data) use (&$result) {
                $result = $data;
            },
            function ($error) {
                throw new \RuntimeException($error);
            }
        );

        $loop->run();
        return $result;
    }

    /**
     * Fetch track details from Yandex Music API.
     */
    public function getTrackInfo(string $token, string $trackId): array
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->get("https://api.music.yandex.net/tracks/{$trackId}", [
            'headers' => [
                'Authorization' => 'OAuth ' . $token,
            ],
        ]);

        $trackData = json_decode($response->getBody(), true)['result'][0];

        // Extract the cover image URL (if available)
        $coverUri = $trackData['coverUri'] ?? null;

        if ($coverUri) {
            // Replace '%%' with the desired size (e.g., '200x200', '400x400', 'orig')
            $trackData['image_url'] = "https://" . str_replace('%%', '200x200', $coverUri);
        }

        return $trackData;
    }
}
