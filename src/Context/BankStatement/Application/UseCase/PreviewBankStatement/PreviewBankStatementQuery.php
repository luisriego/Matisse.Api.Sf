<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Application\UseCase\PreviewBankStatement;

use App\Shared\Application\Query;

/**
 * Query that triggers the preview of an OFX file upload.
 * The handler parses the file, matches against expense history and returns a BankStatementPreviewDto.
 */
final readonly class PreviewBankStatementQuery implements Query
{
    public function __construct(
        /** Raw OFX file content (string) */
        public readonly string $ofxContent,
    ) {}
}
