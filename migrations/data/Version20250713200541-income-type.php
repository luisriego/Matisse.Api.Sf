<?php

declare(strict_types=1);

namespace DoctrineMigrations\data;

use App\Shared\Domain\ValueObject\Uuid;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250713200541 extends AbstractMigration
{
    private const INCOMING_TYPES = [
        // Receitas Ordinárias
        'RC1TC' => ['name' => 'TAXA_CONDOMINIAL', 'description' => 'Recebimento da cota condominial mensal regular dos condôminos.'],
        'RC2JM' => ['name' => 'JUROS_MULTAS_ATRASO', 'description' => 'Recebimento de juros e multas por pagamento de cotas condominiais em atraso.'],

        // Receitas Extraordinárias
        'RC3AE' => ['name' => 'ALUGUEL_ESPACOS', 'description' => 'Receita proveniente do aluguel de espaços comuns (ex: salão de festas, churrasqueira).'],
        'RC4CE' => ['name' => 'COTA_EXTRA', 'description' => 'Recebimento de cotas extras aprovadas em assembleia para fins específicos (obras, melhorias, fundo específico).'],
        'RC5RB' => ['name' => 'REEMBOLSOS', 'description' => 'Recebimento de valores para cobrir danos causados por condôminos/terceiros, ressarcimento de despesas adiantadas, etc.'],

        // Receitas Financeiras
        'RC6RF' => ['name' => 'RENDIMENTOS_FINANCEIROS', 'description' => 'Juros ou rendimentos de aplicações financeiras do fundo de reserva ou outras contas de investimento do condomínio.'],

        // Outras Receitas
        'RC7DV' => ['name' => 'RECEITAS_DIVERSAS', 'description' => 'Outras receitas eventuais não classificadas nas categorias anteriores (ex: venda de materiais recicláveis, multas não relacionadas a atraso de cota, doações).'],
    ];

    public function getDescription(): string
    {
        return 'Seed initial data for income_type table';
    }

    public function up(Schema $schema): void
    {
        // Opcional: Limpia la tabla para asegurar que la inserción sea idempotente
        $this->addSql('DELETE FROM income_type');

        foreach (self::INCOMING_TYPES as $code => $data) {
            // La API es idéntica, lo que facilita el cambio
            $id = Uuid::random()->value();
            $name = $data['name'];
            // Escapar comillas simples para SQL sigue siendo una buena práctica en SQL plano
            $description = str_replace("'", "''", $data['description']);

            $this->addSql(
                "INSERT INTO income_type (id, name, code, description) VALUES ('{$id}', '{$name}', '{$code}', '{$description}')"
            );
        }
    }

    public function down(Schema $schema): void
    {
        $codes = array_map(fn($code) => "'$code'", array_keys(self::INCOMING_TYPES));
        $this->addSql('DELETE FROM income_type WHERE code IN (' . implode(',', $codes) . ')');
    }
}
