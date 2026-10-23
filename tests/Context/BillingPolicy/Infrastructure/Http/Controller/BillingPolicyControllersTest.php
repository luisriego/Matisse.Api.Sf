<?php

declare(strict_types=1);

namespace App\Tests\Context\BillingPolicy\Infrastructure\Http\Controller;

use App\Context\EventStore\Domain\StoredEventRepository;
use App\Tests\Shared\Infrastructure\PhpUnit\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

use function json_encode;

use const JSON_THROW_ON_ERROR;

final class BillingPolicyControllersTest extends ApiTestCase
{
    protected ?EntityManagerInterface $entityManager = null;
    private ?StoredEventRepository $storedEventRepository = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createAuthenticatedClient();

        $container = self::getContainer();
        $this->storedEventRepository = $container->get(StoredEventRepository::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);

        // Limpiar eventos de billing policy previos
        $events = $this->storedEventRepository->findByEventType('monthly.billing.parameters.were.recorded');

        foreach ($events as $event) {
            $this->entityManager->remove($event);
        }
        $this->entityManager->flush();

        // Limpiar snapshots de billing policy previos
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM billing_policy_month_snapshot');
    }

    protected function tearDown(): void
    {
        $this->storedEventRepository = null;
        $this->entityManager = null;
        parent::tearDown();
    }

    public function testPutAndResolveReturnSameAmounts(): void
    {
        $targetMonth = '2026-03';
        $extraFee = 15000;
        $reserveFund = 8000;
        $syndicShare = 60000;
        $gasPrice = 2500;

        // PUT: guardar política del mes
        $this->client->request(
            'PUT',
            '/api/v1/billing-policy/months/' . $targetMonth,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'extraFeePerUnitCents' => $extraFee,
                'reserveFundPerUnitCents' => $reserveFund,
                'syndicShareTotalCents' => $syndicShare,
                'gasPricePerM3Cents' => $gasPrice,
            ], JSON_THROW_ON_ERROR),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // GET: resolver política del mes
        $this->client->request(
            'GET',
            '/api/v1/billing-policy/resolve?targetMonth=' . $targetMonth,
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $content = $this->client->getResponse()->getContent();
        $data = json_decode($content, true);

        $this->assertTrue($data['data']['hasPolicy']);
        $this->assertSame($extraFee, $data['data']['extraFeePerUnitCents']);
        $this->assertSame($reserveFund, $data['data']['reserveFundPerUnitCents']);
        $this->assertSame($syndicShare, $data['data']['syndicShareTotalCents']);
        $this->assertSame($gasPrice, $data['data']['gasPricePerM3Cents']);
    }

    public function testResolveWithoutPolicyReturnsHasPolicyFalse(): void
    {
        $targetMonth = '2026-05';

        $this->client->request(
            'GET',
            '/api/v1/billing-policy/resolve?targetMonth=' . $targetMonth,
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $content = $this->client->getResponse()->getContent();
        $data = json_decode($content, true);

        $this->assertFalse($data['data']['hasPolicy']);
    }
}
