<?php

declare(strict_types=1);

namespace App\Students;

use PDO;
use Throwable;

final class StudentRepository
{
    private const COLUMNS = 'id, name, age, city, created_at, updated_at';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function search(?string $term, ?string $city, int $limit, int $offset): array
    {
        $rows = $this->call('CALL sp_search_students(?, ?, ?, ?)', [
            $term === null ? null : self::escapeLike($term),
            $city,
            $limit,
            $offset,
        ]);

        return array_map([self::class, 'toStudent'], $rows);
    }

    public function count(?string $term, ?string $city): int
    {
        $rows = $this->call('CALL sp_count_students(?, ?)', [
            $term === null ? null : self::escapeLike($term),
            $city,
        ]);

        return (int) $rows[0]['total'];
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM students WHERE id = ?');
        $statement->execute([$id]);
        $row = $statement->fetch();

        return $row ? self::toStudent($row) : null;
    }

    public function create(array $data): array
    {
        $statement = $this->pdo->prepare('INSERT INTO students (name, age, city) VALUES (?, ?, ?)');
        $statement->execute([$data['name'], $data['age'], $data['city']]);

        return $this->find((int) $this->pdo->lastInsertId());
    }

    public function createMany(array $students): array
    {
        $this->pdo->beginTransaction();
        try {
            $created = array_map(fn (array $data) => $this->create($data), $students);
            $this->pdo->commit();
        } catch (Throwable $error) {
            $this->pdo->rollBack();
            throw $error;
        }

        return $created;
    }

    public function update(int $id, array $data): ?array
    {
        $assignments = implode(', ', array_map(fn (string $column) => "{$column} = ?", array_keys($data)));
        $statement = $this->pdo->prepare("UPDATE students SET {$assignments} WHERE id = ?");
        $statement->execute([...array_values($data), $id]);

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM students WHERE id = ?');
        $statement->execute([$id]);

        return $statement->rowCount() > 0;
    }

    public function citySummary(): array
    {
        return array_map(static fn (array $row) => [
            'city' => $row['city'],
            'students' => (int) $row['students'],
            'average_age' => (float) $row['average_age'],
            'youngest' => (int) $row['youngest'],
            'oldest' => (int) $row['oldest'],
        ], $this->call('CALL sp_city_summary()'));
    }

    public function history(int $id): array
    {
        return array_map(static fn (array $row) => [
            'id' => (int) $row['id'],
            'action' => $row['action'],
            'old' => $row['old_data'] === null ? null : json_decode($row['old_data'], true),
            'new' => $row['new_data'] === null ? null : json_decode($row['new_data'], true),
            'changed_at' => $row['changed_at'],
        ], $this->call('CALL sp_student_history(?)', [$id]));
    }

    public static function escapeLike(string $term): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term);
    }

    private function call(string $sql, array $params = []): array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        $rows = $statement->fetchAll();
        $statement->closeCursor();

        return $rows;
    }

    private static function toStudent(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'age' => (int) $row['age'],
            'city' => $row['city'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
        ];
    }
}
