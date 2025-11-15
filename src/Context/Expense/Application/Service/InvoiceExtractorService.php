<?php

declare(strict_types=1);

namespace App\Context\Expense\Application\Service;

use Google\Cloud\DocumentAI\V1\Client\DocumentProcessorServiceClient;
use Google\Cloud\DocumentAI\V1\ProcessRequest;
use Google\Cloud\DocumentAI\V1\RawDocument;

use function file_get_contents;
use function mime_content_type;

readonly class InvoiceExtractorService
{
    public function __construct(
        private string $projectId,
        private string $location,
        private string $processorId,
    ) {}

    public function extractData(string $filePath): array
    {
        $fileContent = file_get_contents($filePath);
        $mimeType = mime_content_type($filePath);

        $rawDocument = new RawDocument([
            'content' => $fileContent,
            'mime_type' => $mimeType,
        ]);

        $processorName = DocumentProcessorServiceClient::processorName(
            $this->projectId,
            $this->location,
            $this->processorId,
        );

        $request = new ProcessRequest([
            'name' => $processorName,
            'raw_document' => $rawDocument,
        ]);

        $client = new DocumentProcessorServiceClient();

        try {
            $result = $client->processDocument($request);
            $document = $result->getDocument();

            $extractedData = [];

            foreach ($document->getEntities() as $entity) {
                $extractedData[$entity->getType()] = $entity->getMentionText();
            }

            return $extractedData;
        } finally {
            $client->close();
        }
    }
}
