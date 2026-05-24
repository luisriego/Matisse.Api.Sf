<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Infrastructure\Matcher;

use App\Context\BankStatement\Application\Matcher\EmbeddingVectorClientInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

use function json_decode;

/**
 * Calls Ollama POST /api/embeddings.
 */
final class OllamaEmbeddingVectorClient implements EmbeddingVectorClientInterface
{
    private Client $http;

    public function __construct(
        private readonly string $ollamaHost,
        private readonly string $embeddingModel,
        private readonly ?LoggerInterface $logger = null,
    ) {
        $this->http = new Client([
            'base_uri' => $this->ollamaHost,
            'timeout'  => 15.0,
        ]);
    }

    public function embed(string $text): ?array
    {
        try {
            $response = $this->http->post('/api/embeddings', [
                'json' => [
                    'model'  => $this->embeddingModel,
                    'prompt' => $text,
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            return $body['embedding'] ?? null;
        } catch (GuzzleException $e) {
            $this->logger?->warning('OllamaEmbeddingVectorClient: embed failed', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
