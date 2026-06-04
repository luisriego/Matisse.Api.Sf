<?php

declare(strict_types=1);

namespace App\Shared\Domain\Catalog;

/**
 * Single source of truth for the predefined expense types catalog.
 * Used both by the manual seed command and the automatic setup seeder.
 */
final class ExpenseTypeCatalog
{
    public const array TYPES = [
        // Manutenção e Reparos (MR)
        'MR1GE' => ['name' => 'Manutenção Geral', 'description' => 'Pequenos reparos (hidráulica, elétrica em áreas comuns, chaveiro, etc.).', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'MR2EV' => ['name' => 'Manutenção do Elevador', 'description' => 'Contratos de manutenção, revisões periódicas, peças, reparos emergenciais.', 'distributionMethod' => 'EQUAL', 'isRecurring' => true],
        'MR3JA' => ['name' => 'Jardinagem e Paisagismo', 'description' => 'Corte de grama, poda de árvores/arbustos, adubação, controle de pragas de jardim, irrigação.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'MR4PR' => ['name' => 'Manutenção Predial', 'description' => 'Pintura de áreas comuns (corredores, hall), reparos em alvenaria/pisos comuns, limpeza de fachada/garagem.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'MR5EQ' => ['name' => 'Manutenção de Equipamentos', 'description' => 'Bombas d\'água, portões eletrônicos, interfones, sistema de CFTV (câmeras).', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'MR6SI' => ['name' => 'Manutenção dos Sistemas de Incêndio', 'description' => 'Recarga/revisão de extintores, teste de mangueiras, manutenção de alarmes e detectores.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'MR7CP' => ['name' => 'Controle de Pragas', 'description' => 'Dedetizações periódicas ou emergenciais (baratas, ratos, cupins, etc.).', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'MR8RO' => ['name' => 'Manutenção Preventiva do Portão', 'description' => 'Manutenção preventiva ou paliativa do portão da garagem).', 'distributionMethod' => 'EQUAL', 'isRecurring' => true],

        // Serviços Públicos / Contas de Consumo (SP)
        'SP1EL' => ['name' => 'CEMIG', 'description' => 'Conta de luz de corredores, elevador(es), bombas, portões, iluminação externa.', 'distributionMethod' => 'EQUAL', 'isRecurring' => true],
        'SP2AG' => ['name' => 'COPASA', 'description' => 'Conta de água/esgoto para limpeza, jardinagem (se não individualizada), consumo da portaria.', 'distributionMethod' => 'FRACTION', 'isRecurring' => true],
        'SP3GA' => ['name' => 'Compra de Gás (Cilindro)', 'description' => 'Aquisição de gás em cilindro para áreas comuns ou uso condominial.', 'distributionMethod' => 'EQUAL', 'isRecurring' => true],
        'SP4TC' => ['name' => 'Internet (CFTV)', 'description' => 'Linha telefônica/internet', 'distributionMethod' => 'EQUAL', 'isRecurring' => true],

        // Pessoal / Folha de Pagamento (PF)
        'PF1SE' => ['name' => 'Salários e Encargos', 'description' => 'Taxa mensal do síndico', 'distributionMethod' => 'EQUAL', 'isRecurring' => true],
        'PF2SE' => ['name' => 'Rateio do Síndico', 'description' => 'Rateio mensal da taxa do síndico entre as unidades.', 'distributionMethod' => 'EQUAL', 'isRecurring' => true],

        // Serviços Terceirizados (ST)
        'ST1LT' => ['name' => 'Limpeza Terceirizada', 'description' => 'Contrato com empresa de limpeza.', 'distributionMethod' => 'EQUAL', 'isRecurring' => true],
        'ST2AJ' => ['name' => 'Assessoria Jurídica/Contábil', 'description' => 'Honorários de advogados, contadores, auditorias.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],

        // Administrativo e Financeiro (AF)
        'AF1DB' => ['name' => 'Despesas Bancárias', 'description' => 'Tarifas de manutenção de conta, taxas de boletos.', 'distributionMethod' => 'EQUAL', 'isRecurring' => true],
        'AF2SG' => ['name' => 'Seguros do Condomínio', 'description' => 'Seguro obrigatório do condomínio (incêndio, etc.), seguro de responsabilidade civil do síndico.', 'distributionMethod' => 'FRACTION', 'isRecurring' => false],
        'AF3ML' => ['name' => 'Material de Limpeza', 'description' => 'Produtos de limpeza, sacos de lixo', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'AF4IT' => ['name' => 'Impostos e Taxas', 'description' => 'IPTU de áreas comuns (se houver), outras taxas municipais/estaduais.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'AF5CC' => ['name' => 'Correios e Cartório', 'description' => 'Despesas com envio de correspondência, reconhecimento de firmas, cópias autenticadas.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],

        'OT1DA' => ['name' => 'Despesas da Assembleia', 'description' => 'Aluguel de espaço (se necessário), cópias de documentos, envio de convocações.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
        'OT2DD' => ['name' => 'Despesas Diversas', 'description' => 'Gastos menores e eventuais não classificáveis nas outras categorias.', 'distributionMethod' => 'EQUAL', 'isRecurring' => false],
    ];

    private function __construct() {}
}
