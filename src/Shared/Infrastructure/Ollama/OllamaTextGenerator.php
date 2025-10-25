<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ollama;

use App\Shared\Application\TextGeneratorInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

class OllamaTextGenerator implements TextGeneratorInterface
{
    public function generate(string $prompt): ?string
    {
        try {
            $response = $this->httpClient->post('/api/generate', [
                'json' => [
                    'model' => 'gemma:7b',
                    'prompt' => $prompt,
                    'stream' => false,
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            return $body['response'] ?? null;

        } catch (\Exception $e) {
            $this->logger?->error('Error connecting to Ollama: ' . $e->getMessage());
            return null;
        } catch (GuzzleException $e) {
            $this->logger?->error('Error connecting to Ollama: ' . $e->getMessage());
            return null;
        }
    }

    private Client $httpClient;
    private LoggerInterface $logger;

    public function __construct(string $ollamaHost, LoggerInterface $logger = null)
    {
        $this->httpClient = new Client(['base_uri' => $ollamaHost, 'timeout' => 60.0]);
        $this->logger = $logger;
    }
}
