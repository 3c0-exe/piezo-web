<?php

namespace App\Services;

use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

class MqttService
{
    private function makeClient(string $clientSuffix = ''): array
    {
        $settings = (new ConnectionSettings)
            ->setUsername(config('mqtt-client.connections.default.connection_settings.auth.username'))
            ->setPassword(config('mqtt-client.connections.default.connection_settings.auth.password'))
            ->setUseTls(true)
            ->setTlsVerifyPeer(false)
            ->setTlsVerifyPeerName(false)
            ->setConnectTimeout(10)
            ->setKeepAliveInterval(10);

        $clientId = config('mqtt-client.connections.default.client_id', 'piezo-laravel') . $clientSuffix;

        $client = new MqttClient(
            config('mqtt-client.connections.default.host'),
            (int) config('mqtt-client.connections.default.port', 8883),
            $clientId
        );

        return [$client, $settings];
    }

    public function publish(string $topic, array $payload, bool $retain = false): void
    {
        [$client, $settings] = $this->makeClient('-pub-' . uniqid());
        $client->connect($settings);
        $client->publish($topic, json_encode($payload), 0, $retain);
        $client->disconnect();
    }

    public function subscribe(string $topic, callable $callback): void
    {
        [$client, $settings] = $this->makeClient();
        $client->connect($settings);
        $client->subscribe($topic, function (string $topic, string $message) use ($callback) {
            $callback($topic, $message);
        }, 0);
        $client->loop(true);
    }
}