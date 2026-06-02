<?php

declare(strict_types=1);

namespace App\Tests\Context\Forecast\Infrastructure\Scenario;

use App\Context\Account\Domain\Account;
use App\Context\BillingPolicy\Application\UseCase\RecordBillingPolicyMonth\RecordBillingPolicyMonthCommand;
use App\Context\Expense\Domain\ExpenseType;
use App\Context\Gas\Application\UseCase\RecordGasReading\RecordGasReadingCommand;
use App\Context\Gas\Application\UseCase\SetGasPrice\SetGasPriceCommand;
use App\Context\ResidentUnit\Domain\ResidentUnit;
use App\Context\ResidentUnit\Domain\ResidentUnitIdealFraction;
use App\Context\ResidentUnit\Domain\ResidentUnitVO;
use App\Tests\Context\Account\Domain\AccountMother;
use App\Tests\Context\Expense\Domain\ExpenseMother;
use App\Tests\Context\Expense\Domain\ExpenseTypeMother;
use App\Tests\Context\ResidentUnit\Domain\ResidentUnitMother;
use App\Tests\Shared\Domain\UuidMother;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\Messenger\MessageBusInterface;

use function array_column;
use function array_fill_keys;
use function array_sum;
use function count;
use function round;
use function sprintf;

/**
 * Reproducible snapshot inspired by dev DB: Dec/2025 gas + Jan/2026 reconciled expenses,
 * billing policy 2026-01, no recurring_expense memory (pre option B).
 */
final class CondominiumJan2026Scenario
{
    public const int BILLING_EXTRA_FEE_PER_UNIT_CENTS = 25_000;

    public const int BILLING_RESERVE_FUND_PER_UNIT_CENTS = 9_370;

    public const int BILLING_SYNDIC_SHARE_TOTAL_CENTS = 60_000;

    public const int GAS_PRICE_PER_M3_CENTS = 2_600;

    public const int JAN_EXPENSE_COUNT = 10;

    public const int JAN_EXPENSE_TOTAL_CENTS = 579_076;

    /**
     * PREVISÃO Febrero/2026 (elaborada tras conciliar Enero/2026): totales esperados según
     * reglas del condominio + lecturas gas dic/25→ene/26 + billing policy 2026-01.
     * Equivalente a las columnas del Excel PREVISÃO (no incluye despesas variables hasta opción B).
     */
    public const int PREVISAO_FEB_2026_SYNDIC_TOTAL_CENTS = 60_000;

    public const int PREVISAO_FEB_2026_GAS_TOTAL_CENTS = 27_392;

    public const int PREVISAO_FEB_2026_EXTRA_TOTAL_CENTS = 125_000;

    public const int PREVISAO_FEB_2026_RESERVE_TOTAL_CENTS = 46_850;

    public const int PREVISAO_FEB_2026_GRAND_TOTAL_CENTS = 259_242;

    /** @var array<string, int> unit label => boleto total cents (PREVISÃO feb/26) */
    private const PREVISAO_FEB_2026_UNIT_TOTAL_CENTS = [
        'Apto 101' => 49_610,
        'Apto 201' => 54_388,
        'Apto 301' => 47_543,
        'Apto 401' => 53_861,
        'Apto 501' => 53_840,
    ];

    /** @var array<string, int> unit label => syndic share cents (60000 / 5) */
    private const PREVISAO_FEB_2026_UNIT_SYNDIC_CENTS = [
        'Apto 101' => 12_000,
        'Apto 201' => 12_000,
        'Apto 301' => 12_000,
        'Apto 401' => 12_000,
        'Apto 501' => 12_000,
    ];
    private const UNITS = [
        ['label' => 'Apto 101', 'fraction' => 0.18131761, 'dec2025' => 363.039, 'jan2026' => 364.285],
        ['label' => 'Apto 201', 'fraction' => 0.18131761, 'dec2025' => 754.816, 'jan2026' => 757.9],
        ['label' => 'Apto 301', 'fraction' => 0.18131761, 'dec2025' => 238.077, 'jan2026' => 238.528],
        ['label' => 'Apto 401', 'fraction' => 0.19816931, 'dec2025' => 645.906, 'jan2026' => 648.787],
        ['label' => 'Apto 501', 'fraction' => 0.25787791, 'dec2025' => 1281.343, 'jan2026' => 1284.216],
    ];

    /** @var list<array{amount: int, dueDate: string, description: string}> */
    private const JAN_EXPENSES = [
        ['amount' => 123_707, 'dueDate' => '2026-01-02', 'description' => 'BOLETO PAGO FACILITY BH LTDA'],
        ['amount' => 950, 'dueDate' => '2026-01-05', 'description' => 'TAR COBRANCA EXP'],
        ['amount' => 17_900, 'dueDate' => '2026-01-05', 'description' => 'TAR CONTA CERTA 12/25'],
        ['amount' => 12_000, 'dueDate' => '2026-01-06', 'description' => 'PIX ENVIADO ROBERT KRAMBERGER 326.188.406-15'],
        ['amount' => 48_500, 'dueDate' => '2026-01-06', 'description' => 'PIX ENVIADO ROBERT KRAMBERGER 326.188.406-15'],
        ['amount' => 55_556, 'dueDate' => '2026-01-06', 'description' => 'DA COPASA 00011211563'],
        ['amount' => 108_551, 'dueDate' => '2026-01-12', 'description' => 'BOLETO PAGO ELEVADORES ATLAS SCHINDLE'],
        ['amount' => 108_551, 'dueDate' => '2026-01-12', 'description' => 'BOLETO PAGO ELEVADORES ATLAS SCHINDLE'],
        ['amount' => 27_306, 'dueDate' => '2026-01-14', 'description' => 'DA CEMIG 000042299933'],
        ['amount' => 76_055, 'dueDate' => '2026-01-29', 'description' => 'PIX ENVIADO GERALDO CARMELUCIO DOS SANTOS'],
    ];

    /** @var array<string, ResidentUnit> */
    public readonly array $unitsByLabel;

    public readonly Account $ledgerAccount;

    public readonly ExpenseType $expenseType;

    private function __construct(
        array $unitsByLabel,
        Account $ledgerAccount,
        ExpenseType $expenseType,
    ) {
        $this->unitsByLabel = $unitsByLabel;
        $this->ledgerAccount = $ledgerAccount;
        $this->expenseType = $expenseType;
    }

    public static function seed(EntityManagerInterface $em, MessageBusInterface $commandBus): self
    {
        $account = AccountMother::create();
        $em->persist($account);

        $expenseType = ExpenseTypeMother::create(
            name: 'MR1GE',
            description: 'General expense',
        );
        $em->persist($expenseType);

        $unitsByLabel = [];

        foreach (self::UNITS as $spec) {
            $unit = ResidentUnitMother::create(
                unit: new ResidentUnitVO($spec['label']),
                idealFraction: new ResidentUnitIdealFraction($spec['fraction']),
            );
            $em->persist($unit);
            $unitsByLabel[$spec['label']] = $unit;
        }

        $em->flush();

        $commandBus->dispatch(new SetGasPriceCommand(self::GAS_PRICE_PER_M3_CENTS));

        foreach (self::UNITS as $spec) {
            $unitId = $unitsByLabel[$spec['label']]->id();
            $commandBus->dispatch(new RecordGasReadingCommand(
                UuidMother::create(),
                $unitId,
                2025,
                12,
                $spec['dec2025'],
            ));
            $commandBus->dispatch(new RecordGasReadingCommand(
                UuidMother::create(),
                $unitId,
                2026,
                1,
                $spec['jan2026'],
            ));
        }

        $commandBus->dispatch(new RecordBillingPolicyMonthCommand(
            '2026-01',
            self::BILLING_EXTRA_FEE_PER_UNIT_CENTS,
            self::BILLING_RESERVE_FUND_PER_UNIT_CENTS,
            self::BILLING_SYNDIC_SHARE_TOTAL_CENTS,
            self::GAS_PRICE_PER_M3_CENTS,
        ));

        foreach (self::JAN_EXPENSES as $row) {
            $expense = ExpenseMother::create(
                amount: $row['amount'],
                type: $expenseType,
                account: $account,
                dueDate: new DateTime($row['dueDate']),
                description: $row['description'],
            );
            $em->persist($expense);
        }

        $em->flush();

        return new self($unitsByLabel, $account, $expenseType);
    }

    public function unitCount(): int
    {
        return count(self::UNITS);
    }

    /**
     * Gas for forecast target month M uses readings from calendar month M−1 vs its previous month.
     *
     * @return array<string, int> label => gas cents
     */
    public static function expectedGasByUnitForForecastTargetMonth(string $targetMonth): array
    {
        if ($targetMonth === '2026-02') {
            return self::gasByUnitFromReadings('jan2026', 'dec2025');
        }

        if ($targetMonth === '2026-01') {
            return array_fill_keys(array_column(self::UNITS, 'label'), 0);
        }

        throw new InvalidArgumentException(sprintf('Unsupported targetMonth %s in scenario.', $targetMonth));
    }

    public static function expectedGasTotalForForecastTargetMonth(string $targetMonth): int
    {
        return array_sum(self::expectedGasByUnitForForecastTargetMonth($targetMonth));
    }

    /**
     * Grand total for PREVISÃO: gas + extra + reserve + syndic (billing policy).
     */
    public static function expectedGrandTotalForForecastTargetMonth(string $targetMonth): int
    {
        if ($targetMonth === '2026-02') {
            return self::PREVISAO_FEB_2026_GRAND_TOTAL_CENTS;
        }

        $n = count(self::UNITS);
        $gas = self::expectedGasTotalForForecastTargetMonth($targetMonth);
        $extra = self::BILLING_EXTRA_FEE_PER_UNIT_CENTS * $n;
        $reserve = self::BILLING_RESERVE_FUND_PER_UNIT_CENTS * $n;

        return $gas + $extra + $reserve + self::PREVISAO_FEB_2026_SYNDIC_TOTAL_CENTS;
    }

    /**
     * @return array<string, int>
     */
    public static function previsaoGoldenUnitTotals(string $targetMonth): array
    {
        if ($targetMonth !== '2026-02') {
            throw new InvalidArgumentException('Golden PREVISÃO unit totals defined for 2026-02 only.');
        }

        return self::PREVISAO_FEB_2026_UNIT_TOTAL_CENTS;
    }

    /**
     * @return array<string, int>
     */
    public static function previsaoGoldenUnitSyndicCents(string $targetMonth): array
    {
        if ($targetMonth !== '2026-02') {
            throw new InvalidArgumentException('Golden PREVISÃO syndic cents defined for 2026-02 only.');
        }

        return self::PREVISAO_FEB_2026_UNIT_SYNDIC_CENTS;
    }

    /**
     * @return array<string, int>
     */
    private static function gasByUnitFromReadings(string $currentKey, string $previousKey): array
    {
        $out = [];

        foreach (self::UNITS as $spec) {
            $consumption = $spec[$currentKey] - $spec[$previousKey];

            if ($consumption < 0) {
                $consumption = 0.0;
            }
            $out[$spec['label']] = (int) round($consumption * self::GAS_PRICE_PER_M3_CENTS);
        }

        return $out;
    }
}
