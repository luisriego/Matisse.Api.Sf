<?php

declare(strict_types=1);

namespace App\Context\BankStatement\Infrastructure\Persistence\Doctrine;

use App\Context\BankStatement\Domain\ExpenseEmbedding;
use App\Context\BankStatement\Domain\ExpenseEmbeddingRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use function implode;
use function sprintf;

final class DoctrineExpenseEmbeddingRepository extends ServiceEntityRepository implements ExpenseEmbeddingRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExpenseEmbedding::class);
    }

    public function upsert(ExpenseEmbedding $embedding): void
    {
        $conn = $this->getEntityManager()->getConnection();

        $vectorStr = '[' . implode(',', $embedding->vector()) . ']';

        $conn->executeStatement(
            <<<SQL
            INSERT INTO expense_embedding (id, expense_id, vector, description, embedding_model, indexed_at)
            VALUES (:id, :expenseId, :vector::vector, :description, :embeddingModel, NOW())
            ON CONFLICT (expense_id)
            DO UPDATE SET
                id              = EXCLUDED.id,
                vector          = EXCLUDED.vector,
                description     = EXCLUDED.description,
                embedding_model = EXCLUDED.embedding_model,
                indexed_at      = EXCLUDED.indexed_at
            SQL,
            [
                'id'             => $embedding->id(),
                'expenseId'      => $embedding->expenseId(),
                'vector'         => $vectorStr,
                'description'    => $embedding->description(),
                'embeddingModel' => $embedding->embeddingModel(),
            ],
        );
    }

    public function findSimilar(array $queryVector, string $embeddingModel, int $topK = 3): array
    {
        $vectorStr = '[' . implode(',', $queryVector) . ']';

        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            sprintf(
                <<<SQL
                SELECT expense_id,
                       description,
                       1 - (vector <=> :vector::vector) AS score
                FROM expense_embedding
                WHERE embedding_model = :model
                ORDER BY vector <=> :vector::vector ASC
                LIMIT %d
                SQL,
                $topK,
            ),
            [
                'vector' => $vectorStr,
                'model'  => $embeddingModel,
            ],
        );

        return array_map(
            static fn (array $row) => [
                'expenseId'   => $row['expense_id'],
                'description' => $row['description'],
                'score'       => (float) $row['score'],
            ],
            $rows,
        );
    }

    public function deleteByExpenseId(string $expenseId): void
    {
        $this->getEntityManager()->getConnection()->executeStatement(
            'DELETE FROM expense_embedding WHERE expense_id = :expenseId',
            ['expenseId' => $expenseId],
        );
    }

    public function countIndexed(): int
    {
        return (int) $this->getEntityManager()->getConnection()
            ->fetchOne('SELECT COUNT(*) FROM expense_embedding');
    }
}
