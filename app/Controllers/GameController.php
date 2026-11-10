<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Models\Game;
use App\Services\BackgammonEngine;
use DomainException;
use Throwable;

final class GameController
{
    private Game $games;
    private BackgammonEngine $engine;

    public function __construct()
    {
        $this->games = new Game();
        $this->engine = new BackgammonEngine();
    }

    private function input(): array
    {
        $data = json_decode(file_get_contents('php://input'), true);
        return is_array($data) ? $data : [];
    }

    private function fail(Throwable $e, int $status = 422): never
    {
        Response::json(['success' => false, 'message' => $e->getMessage()], $status);
    }

    private function authorized(array $input): array
    {
        $code = strtoupper(trim((string)($input['room_code'] ?? '')));
        $token = (string)($input['player_token'] ?? '');
        if ($code === '' || $token === '') throw new DomainException('کد اتاق یا توکن بازیکن ارسال نشده است.');

        $game = $this->games->findByCode($code);
        if (!$game) throw new DomainException('اتاق پیدا نشد.');
        $isHost = hash_equals((string)$game['host_token'], $token);
        $isGuest = $game['guest_token'] !== null && hash_equals((string)$game['guest_token'], $token);
        if (!$isHost && !$isGuest) throw new DomainException('توکن بازیکن معتبر نیست.');
        $game['player_color'] = $isHost ? BackgammonEngine::WHITE : BackgammonEngine::BLACK;
        return $game;
    }

    public function create(): never
    {
        try {
            $state = $this->engine->newGame();
            $token = bin2hex(random_bytes(32));
            $code = '';
            for ($i = 0; $i < 10; $i++) {
                $candidate = strtoupper(substr(bin2hex(random_bytes(4)), 0, 5));
                if (!$this->games->findByCode($candidate)) { $code = $candidate; break; }
            }
            if ($code === '') throw new DomainException('ساخت کد اتاق ممکن نشد؛ دوباره تلاش کن.');
            $this->games->create($code, $token, $state);
            Response::json([
                'success' => true, 'room_code' => $code,
                'player_token' => $token, 'color' => BackgammonEngine::WHITE,
                'state' => $state,
            ], 201);
        } catch (Throwable $e) { $this->fail($e, 500); }
    }

    public function join(): never
    {
        try {
            $input = $this->input();
            $code = strtoupper(trim((string)($input['room_code'] ?? '')));
            if ($code === '') throw new DomainException('کد اتاق را وارد کن.');
            $game = $this->games->findByCode($code);
            if (!$game) throw new DomainException('اتاق پیدا نشد.');
            if ($game['status'] !== 'waiting') throw new DomainException('این اتاق قبلاً پر شده است.');

            $token = bin2hex(random_bytes(32));
            if (!$this->games->join($code, $token)) throw new DomainException('اتاق همین الان توسط بازیکن دیگری پر شد.');
            $game = $this->games->findByCode($code);
            Response::json([
                'success' => true, 'room_code' => $code,
                'player_token' => $token, 'color' => BackgammonEngine::BLACK,
                'state' => $game['state'],
            ]);
        } catch (Throwable $e) { $this->fail($e); }
    }

    public function state(): never
    {
        try {
            $game = $this->authorized($this->input());
            Response::json([
                'success' => true, 'status' => $game['status'],
                'state' => $game['state'],
            ]);
        } catch (Throwable $e) { $this->fail($e); }
    }

    public function roll(): never
    {
        try {
            $input = $this->input();
            $game = $this->authorized($input);
            $this->assertTurn($game);
            $state = $game['state'];
            $result = $this->engine->roll($state);
            $this->games->updateState($game['room_code'], $state);
            Response::json(['success' => true, 'result' => $result, 'state' => $state]);
        } catch (Throwable $e) { $this->fail($e); }
    }

    public function move(): never
    {
        try {
            $input = $this->input();
            $game = $this->authorized($input);
            $this->assertTurn($game);
            if (!array_key_exists('from', $input) || !array_key_exists('to', $input)) {
                throw new DomainException('مبدا و مقصد حرکت الزامی است.');
            }
            $state = $game['state'];
            $move = $this->engine->move(
                $state, $input['from'], $input['to'],
                isset($input['die']) ? (int)$input['die'] : null
            );
            $this->games->updateState($game['room_code'], $state);
            Response::json(['success' => true, 'move' => $move, 'state' => $state]);
        } catch (Throwable $e) { $this->fail($e); }
    }

    private function assertTurn(array $game): void
    {
        if ($game['status'] !== 'playing') throw new DomainException('هنوز بازیکن دوم وارد اتاق نشده است.');
        if ((int)$game['state']['turn'] !== (int)$game['player_color']) {
            throw new DomainException('الان نوبت شما نیست.');
        }
    }
}
