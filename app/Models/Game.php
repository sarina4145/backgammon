<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Game
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(string $roomCode, string $hostToken, array $state): void
    {
        $sql = "INSERT INTO games (room_code, host_token, state_json, status)
                VALUES (:code, :token, :state, 'waiting')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'code' => $roomCode,
            'token' => $hostToken,
            'state' => json_encode($state, JSON_THROW_ON_ERROR),
        ]);
    }

    public function findByCode(string $roomCode): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM games WHERE room_code = :code LIMIT 1');
        $stmt->execute(['code' => strtoupper($roomCode)]);
        $row = $stmt->fetch();
        return $row ? $this->decode($row) : null;
    }

    public function join(string $roomCode, string $guestToken): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE games SET guest_token = :token, status = 'playing'
             WHERE room_code = :code AND guest_token IS NULL AND status = 'waiting'"
        );
        $stmt->execute(['token' => $guestToken, 'code' => strtoupper($roomCode)]);
        return $stmt->rowCount() === 1;
    }

    public function updateState(string $roomCode, array $state): void
    {
        $status = $state['winner'] === null ? 'playing' : 'finished';
        $stmt = $this->db->prepare(
            'UPDATE games SET state_json = :state, status = :status WHERE room_code = :code'
        );
        $stmt->execute([
            'state' => json_encode($state, JSON_THROW_ON_ERROR),
            'status' => $status,
            'code' => strtoupper($roomCode),
        ]);
    }

    private function decode(array $row): array
    {
        $row['state'] = json_decode($row['state_json'], true, 512, JSON_THROW_ON_ERROR);
        unset($row['state_json']);
        return $row;
    }
}
