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
    public function test_should_split_returns_false_when_disabled(): void
    {
        $resolver = new SettlementAccountResolver(
            $this->emptyAccountRepo(),
            $this->emptyIncomeTypeRepo(),
            false,
        );

        $this->assertFalse($resolver->shouldSplit());
    }

    public function test_should_split_returns_false_when_no_accounts_match(): void
    {
        $resolver = new SettlementAccountResolver(
            $this->emptyAccountRepo(),
            $this->emptyIncomeTypeRepo(),
            true,
        );

        $this->assertFalse($resolver->shouldSplit());
    }

    public function test_should_split_returns_true_when_all_components_resolve(): void
    {
        $resolver = $this->buildFullResolver();

        $this->assertTrue($resolver->shouldSplit());
    }

    public function test_base_resolves_to_conta_principal_and_taxa_condominial(): void
    {
        $resolver = $this->buildFullResolver();

        $result = $resolver->accountAndTypeFor('base');
        $this->assertSame('acc-principal', $result['accountId']);
        $this->assertSame('it-condominial', $result['incomeTypeId']);
    }

    public function test_syndic_resolves_to_conta_principal_and_taxa_condominial(): void
    {
        $resolver = $this->buildFullResolver();

        $result = $resolver->accountAndTypeFor('syndic');
        $this->assertSame('acc-principal', $result['accountId']);
        $this->assertSame('it-condominial', $result['incomeTypeId']);
    }

    public function test_extra_resolves_to_fundo_de_obra_and_cota_extra(): void
    {
        $resolver = $this->buildFullResolver();

        $result = $resolver->accountAndTypeFor('extra');
        $this->assertSame('acc-obra', $result['accountId']);
        $this->assertSame('it-extra', $result['incomeTypeId']);
    }

    public function test_reserve_resolves_to_fundo_de_reserva_and_taxa_condominial(): void
    {
        $resolver = $this->buildFullResolver();

        $result = $resolver->accountAndTypeFor('reserve');
        $this->assertSame('acc-reserva', $result['accountId']);
        $this->assertSame('it-condominial', $result['incomeTypeId']);
    }

    public function test_gas_resolves_to_conta_gas_and_taxa_condominial(): void
    {
        $resolver = $this->buildFullResolver();

        $result = $resolver->accountAndTypeFor('gas');
        $this->assertSame('acc-gas', $result['accountId']);
        $this->assertSame('it-condominial', $result['incomeTypeId']);
    }

    public function test_account_and_type_for_throws_on_unknown_component(): void
    {
        $resolver = $this->buildFullResolver();

        $this->expectException(InvalidArgumentException::class);
        $resolver->accountAndTypeFor('unknown');
    }

    public function test_should_split_returns_false_when_gas_account_missing(): void
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

    public function test_resolution_is_cached_across_calls(): void
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

    /** @param Account[] $accounts */
    private function accountRepoReturning(array $accounts): AccountRepository
    {
        $repo = $this->createMock(AccountRepository::class);
        $repo->method('findAllActive')->willReturn($accounts);

        return $repo;
    }

    /** @param IncomeType[] $types */
    private function incomeTypeRepoReturning(array $types): IncomeTypeRepository
    {
        $repo = $this->createMock(IncomeTypeRepository::class);
        $repo->method('findAll')->willReturn($types);

        return $repo;
    }
}
