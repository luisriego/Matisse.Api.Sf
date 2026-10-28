<?php

declare(strict_types=1);

namespace App\Tests\Context\Slip\Application\Service;

use App\Context\Slip\Application\Service\SlipWorkflowService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Workflow\Exception\LogicException;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\Yaml\Yaml;

use function class_exists;
use function count;

final class SlipWorkflowServiceTest extends TestCase
{
    private WorkflowInterface $slipWorkflow;
    private SlipWorkflowService $service;

    protected function setUp(): void
    {
        $this->slipWorkflow = $this->createMock(WorkflowInterface::class);
        $this->service = new SlipWorkflowService($this->slipWorkflow);
    }

    #[Test]
    public function testSendAppliesSendTransitionWhenAllowed(): void
    {
        $slip = new \stdClass();

        $this->slipWorkflow->expects($this->once())
            ->method('can')
            ->with($slip, 'send')
            ->willReturn(true);

        $this->slipWorkflow->expects($this->once())
            ->method('apply')
            ->with($slip, 'send');

        $this->service->send($slip);
    }

    #[Test]
    public function testCancelAppliesFirstAvailableVoidTransition(): void
    {
        $slip = new \stdClass();

        // Simulate that 'void_from_submitted' is the allowed transition
        $this->slipWorkflow->expects($this->exactly(3))
            ->method('can')
            ->willReturnMap([
                [$slip, 'void_from_pending', false],
                [$slip, 'void_from_submitted', true],
            ]);

        $this->slipWorkflow->expects($this->once())
            ->method('apply')
            ->with($slip, 'void_from_submitted');

        $this->service->cancel($slip);
    }

    #[Test]
    public function testCancelThrowsLogicExceptionWhenNoVoidTransitionIsAllowed(): void
    {
        $slip = new \stdClass();

        // Simulate that no cancel transitions are allowed
        $this->slipWorkflow->expects($this->exactly(3))
            ->method('can')
            ->willReturn(false);

        $this->slipWorkflow->expects($this->never())
            ->method('apply');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cancellation not allowed from current state.');

        $this->service->cancel($slip);
    }

    #[Test]
    public function testIncomeRoutesYamlHasValidRouteNamesAndExistingControllers(): void
    {
        $routesPath = getcwd() . '/src/Context/Income/Infrastructure/Http/routes.yaml';
        $routes = Yaml::parseFile($routesPath);

        // Verify the corrupted key does not exist
        $this->assertArrayNotHasKey('para que income_health_check', $routes,
            'The corrupted route key "para que income_health_check" must be removed');

        // Verify all active controllers exist
        foreach ($routes as $routeName => $routeConfig) {
            if (!isset($routeConfig['controller'])) {
                continue;
            }
            $this->assertTrue(class_exists($routeConfig['controller']),
                sprintf('Controller "%s" for route "%s" does not exist', $routeConfig['controller'], $routeName));
        }

        // Verify there is at least one active route
        $activeRoutes = array_filter($routes, fn ($r) => isset($r['controller']));
        $this->assertGreaterThan(0, count($activeRoutes), 'There must be at least one active income route');
    }
}
