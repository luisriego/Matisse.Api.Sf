<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure;

use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Symfony\Component\HttpFoundation\Response;

use function json_decode;
use function preg_match;
use function sprintf;

final class OpenApiContractTest extends ApiTestCase
{
    private array $doc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->request('GET', '/api/v1/doc.json');
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $content = (string) $this->client->getResponse()->getContent();
        self::assertNotEmpty($content, 'OpenAPI doc must not be empty');

        $decoded = json_decode($content, true);
        self::assertIsArray($decoded, 'OpenAPI doc must be a valid JSON object');

        $this->doc = $decoded;
    }

    public function testResponseIsOpenApi30(): void
    {
        self::assertArrayHasKey('openapi', $this->doc);
        self::assertMatchesRegularExpression(
            '#^3\.\d+\.\d+$#',
            (string) $this->doc['openapi'],
            'Must be OpenAPI 3.x.x',
        );
    }

    public function testAllApiV1RoutesHaveTags(): void
    {
        $paths = $this->doc['paths'] ?? [];
        self::assertIsArray($paths);

        $undocumented = [];

        foreach ($paths as $path => $methods) {
            if (!is_array($methods)) {
                continue;
            }
            foreach ($methods as $method => $spec) {
                if (!is_array($spec)) {
                    continue;
                }
                $tags = $spec['tags'] ?? null;
                if (empty($tags)) {
                    $undocumented[] = sprintf('%s %s', strtoupper($method), $path);
                }
            }
        }

        self::assertEmpty(
            $undocumented,
            sprintf('Operations without tags: %s', implode(', ', $undocumented)),
        );
    }

    public function testAuthenticatedRoutesRequireBearerAuth(): void
    {
        $paths = $this->doc['paths'] ?? [];
        self::assertIsArray($paths);

        // Public paths that should NOT require security
        $publicPatterns = [
            '/api/v1/accounts/health-check',
            '/api/v1/expenses/health-check',
            '/api/v1/resident-unit/health-check',
            '/api/v1/slips/health-check',
            '/api/v1/users/health-check',
            '/api/v1/login_check',
            '/api/v1/users/register',
            '/api/v1/users/activate/',
            '/api/v1/users/password-reset-request',
            '/api/v1/users/password-reset/',
            '/api/v1/users/{userId}/password-reset/',
            '/api/v1/users/confirmation-resend',
            '/api/v1/doc',
        ];

        $violations = [];

        foreach ($paths as $path => $methods) {
            if (!is_array($methods)) {
                continue;
            }
            foreach ($methods as $method => $spec) {
                if (!is_array($spec)) {
                    continue;
                }

                $security = $spec['security'] ?? null;
                $isPublic = false;

                foreach ($publicPatterns as $pubPattern) {
                    if (str_starts_with($path, $pubPattern) || $path === rtrim($pubPattern, '/')) {
                        $isPublic = true;
                        break;
                    }
                }

                // If route is NOT public and has no security defined, it's a violation
                if (!$isPublic && ($security === null || $security === [])) {
                    $violations[] = sprintf('%s %s (no security)', strtoupper($method), $path);
                }
            }
        }

        self::assertEmpty(
            $violations,
            sprintf('Authenticated routes missing bearerAuth: %s', implode(', ', $violations)),
        );
    }

    public function testSecuritySchemesAreDefined(): void
    {
        $components = $this->doc['components'] ?? [];
        self::assertIsArray($components);

        $securitySchemes = $components['securitySchemes'] ?? [];
        self::assertIsArray($securitySchemes);
        self::assertArrayHasKey('bearerAuth', $securitySchemes);

        $scheme = $securitySchemes['bearerAuth'];
        self::assertSame('http', $scheme['type']);
        // OpenAPI 3.0 uses lowercase 'bearer' per RFC 7235
        self::assertSame('bearer', (string) $scheme['scheme']);
    }

    public function testEveryTagIsTitleCased(): void
    {
        $paths = $this->doc['paths'] ?? [];
        self::assertIsArray($paths);

        $tags = [];

        foreach ($paths as $methods) {
            if (!is_array($methods)) {
                continue;
            }
            foreach ($methods as $method => $spec) {
                if (!is_array($spec)) {
                    continue;
                }
                if (isset($spec['tags']) && is_array($spec['tags'])) {
                    $tags = array_merge($tags, $spec['tags']);
                }
            }
        }

        // Tags should be title-cased (no all-lowercase tags)
        $lowercaseTags = array_filter(
            $tags,
            static function (string $tag): bool {
                return $tag === strtolower($tag);
            },
        );

        self::assertEmpty(
            $lowercaseTags,
            sprintf('Tags should be title-cased: %s', implode(', ', array_unique($lowercaseTags))),
        );
    }

    public function testOpenApiDocHasInfoSection(): void
    {
        self::assertArrayHasKey('info', $this->doc);
        self::assertArrayHasKey('title', $this->doc['info']);
        self::assertIsString($this->doc['info']['title']);
        self::assertNotEmpty($this->doc['info']['title']);
    }

    public function testOpenApiDocHasPaths(): void
    {
        self::assertArrayHasKey('paths', $this->doc);
        self::assertIsArray($this->doc['paths']);
        self::assertGreaterThan(0, count($this->doc['paths']), 'Must have at least one documented path');
    }
}
