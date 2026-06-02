<?php

declare(strict_types=1);

namespace App\Tests\Context\BankStatement\Application\Service;

use App\Context\Account\Domain\Account;
use App\Context\Account\Domain\AccountRepository;
use App\Context\BankStatement\Application\Service\SettlementAccountResolver;
use App\Context\Income\Domain\IncomeType;
use App\Context\Income\Domain\IncomeTypeRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SettlementAccountResolverTest extends TestCase
{
    public function testShouldSplitReturnsFalseWhenDisabled(): void
    {
        $resolver = new SettlementAccountResolver(
            $this->emptyAccountRepo(),
            $this->emptyIncomeTypeRepo(),
            false,
        );

        $this->assertFalse($resolver->shouldSplit());
    }

    public function testShouldSplitReturnsFalseWhenNoAccountsMatch(): void
    {
        $resolver = new SettlementAccountResolver(
            $this->emptyAccountRepo(),
            $this->emptyIncomeTypeRepo(),
            true,
        );

        $this->assertFalse($resolver->shouldSplit());
    }

    public function testShouldSplitReturnsTrueWhenAllComponentsResolve(): void
    {
        $resolver = $this->buildFullResolver();

        $this->assertTrue($resolver->shouldSplit());
    }

    public function testBaseResolvesToContaPrincipalAndTaxaCondominial(): void
    {
        $resolver = $this->buildFullResolver();

        $result = $resolver->accountAndTypeFor('base');
        $this->assertSame('acc-principal', $result['accountId']);
        $this->assertSame('it-condominial', $result['incomeTypeId']);
    }

    public function testSyndicResolvesToContaPrincipalAndTaxaCondominial(): void
    {
        $resolver = $this->buildFullResolver();

        $result = $resolver->accountAndTypeFor('syndic');
        $this->assertSame('acc-principal', $result['accountId']);
        $this->assertSame('it-condominial', $result['incomeTypeId']);
    }

    public function testExtraResolvesToFundoDeObraAndCotaExtra(): void
    {
        $resolver = $this->buildFullResolver();

        $result = $resolver->accountAndTypeFor('extra');
        $this->assertSame('acc-obra', $result['accountId']);
        $this->assertSame('it-extra', $result['incomeTypeId']);
    }

    public function testReserveResolvesToFundoDeReservaAndTaxaCondominial(): void
    {
        $resolver = $this->buildFullResolver();

        $result = $resolver->accountAndTypeFor('reserve');
        $this->assertSame('acc-reserva', $result['accountId']);
        $this->assertSame('it-condominial', $result['incomeTypeId']);
    }

    public function testGasResolvesToContaGasAndTaxaCondominial(): void
    {
        $resolver = $this->buildFullResolver();

        $result = $resolver->accountAndTypeFor('gas');
        $this->assertSame('acc-gas', $result['accountId']);
        $this->assertSame('it-condominial', $result['incomeTypeId']);
    }

    public function testAccountAndTypeForThrowsOnUnknownComponent(): void
    {
        $resolver = $this->buildFullResolver();

        $this->expectException(InvalidArgumentException::class);
        $resolver->accountAndTypeFor('unknown');
    }

    public function testShouldSplitReturnsFalseWhenGasAccountMissing(): void
    {
        $accounts = [
            $this->stubAccount('acc-principal', 'Conta Principal'),
            $this->stubAccount('acc-obra', 'Fundo de Obra'),
            $this->stubAccount('acc-reserva', 'Fundo de Reserva'),
        ];

        $incomeTypes = [
            $this->stubIncomeType('it-condominial', 'Taxa Condominial'),
            $this->stubIncomeType('it-extra', 'Cota Extra'),
        ];

        $resolver = new SettlementAccountResolver(
            $this->accountRepoReturning($accounts),
            $this->incomeTypeRepoReturning($incomeTypes),
            true,
        );

        $this->assertFalse($resolver->shouldSplit());
    }

    public function testResolutionIsCachedAcrossCalls(): void
    {
        $accountRepo = $this->createMock(AccountRepository::class);
        $accountRepo->expects($this->once())->method('findAllActive')->willReturn([
            $this->stubAccount('acc-principal', 'Conta Principal'),
            $this->stubAccount('acc-obra', 'Fundo de Obra'),
            $this->stubAccount('acc-reserva', 'Fundo de Reserva'),
            $this->stubAccount('acc-gas', 'Conta Gás'),
        ]);

        $incomeTypeRepo = $this->createMock(IncomeTypeRepository::class);
        $incomeTypeRepo->expects($this->once())->method('findAll')->willReturn([
            $this->stubIncomeType('it-condominial', 'Taxa Condominial'),
            $this->stubIncomeType('it-extra', 'Cota Extra'),
        ]);

        $resolver = new SettlementAccountResolver($accountRepo, $incomeTypeRepo, true);

        $resolver->shouldSplit();
        $resolver->accountAndTypeFor('base');
        $resolver->accountAndTypeFor('gas');
    }

    // ------------------------------------------------------------------

    private function buildFullResolver(): SettlementAccountResolver
    {
        $accounts = [
            $this->stubAccount('acc-principal', 'Conta Principal'),
            $this->stubAccount('acc-obra', 'Fundo de Obra'),
            $this->stubAccount('acc-reserva', 'Fundo de Reserva'),
            $this->stubAccount('acc-gas', 'Conta Gás'),
        ];

        $incomeTypes = [
            $this->stubIncomeType('it-condominial', 'Taxa Condominial'),
            $this->stubIncomeType('it-extra', 'Cota Extra'),
        ];

        return new SettlementAccountResolver(
            $this->accountRepoReturning($accounts),
            $this->incomeTypeRepoReturning($incomeTypes),
            true,
        );
    }

    private function stubAccount(string $id, string $name): Account
    {
        $account = $this->createMock(Account::class);
        $account->method('id')->willReturn($id);
        $account->method('name')->willReturn($name);

        return $account;
    }

    private function stubIncomeType(string $id, string $name): IncomeType
    {
        $type = $this->createMock(IncomeType::class);
        $type->method('id')->willReturn($id);
        $type->method('name')->willReturn($name);

        return $type;
    }

    private function emptyAccountRepo(): AccountRepository
    {
        return $this->accountRepoReturning([]);
    }

    private function emptyIncomeTypeRepo(): IncomeTypeRepository
    {
        return $this->incomeTypeRepoReturning([]);
    }

    /**
     * @param Account[] $accounts
     */
    private function accountRepoReturning(array $accounts): AccountRepository
    {
        $repo = $this->createMock(AccountRepository::class);
        $repo->method('findAllActive')->willReturn($accounts);

        return $repo;
    }

    /**
     * @param IncomeType[] $types
     */
    private function incomeTypeRepoReturning(array $types): IncomeTypeRepository
    {
        $repo = $this->createMock(IncomeTypeRepository::class);
        $repo->method('findAll')->willReturn($types);

        return $repo;
    }
}
