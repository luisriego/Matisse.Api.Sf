<?php

declare(strict_types=1);

namespace App\Shared\Domain\Catalog;

/**
 * Single source of truth for the predefined income types catalog.
 * Used both by the manual seed command and the automatic setup seeder.
 */
final class IncomeTypeCatalog
{
    public const array TYPES = [
        // Receitas Ordinárias
        'RC1TC' => ['name' => 'Taxa Condominial', 'description' => 'Recebimento da cota condominial mensal regular dos condôminos.'],
        'RC2JM' => ['name' => 'Juros e Multas por Atraso', 'description' => 'Recebimento de juros e multas por pagamento de cotas condominiais em atraso.'],

        // Receitas Extraordinárias
        'RC3AE' => ['name' => 'Aluguel de Espaços Comuns', 'description' => 'Receita proveniente do aluguel de espaços comuns (ex: salão de festas, churrasqueira).'],
        'RC4CE' => ['name' => 'Cota Extra', 'description' => 'Recebimento de cotas extras aprovadas em assembleia para fins específicos (obras, melhorias, fundo específico).'],
        'RC5RB' => ['name' => 'Reembolsos', 'description' => 'Recebimento de valores para cobrir danos causados por condôminos/terceiros, ressarcimento de despesas adiantadas, etc.'],

        // Receitas Financeiras
        'RC6RF' => ['name' => 'Rendimentos Financeiros', 'description' => 'Juros ou rendimentos de aplicações financeiras do fundo de reserva ou outras contas de investimento do condomínio.'],

        // Outras Receitas
        'RC7DV' => ['name' => 'Receitas Diversas', 'description' => 'Outras receitas eventuais não classificadas nas categorias anteriores (ex: venda de materiais recicláveis, multas não relacionadas a atraso de cota, doações).'],
    ];

    private function __construct() {}
}
