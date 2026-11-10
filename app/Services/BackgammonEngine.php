<?php
declare(strict_types=1);

namespace App\Services;

use DomainException;

final class BackgammonEngine
{
    public const WHITE = 1;
    public const BLACK = -1;

    public function newGame(): array
    {
        $board = array_fill(0, 24, ['count' => 0, 'color' => null]);
        $this->setPoint($board, 0, 2, self::WHITE);
        $this->setPoint($board, 11, 5, self::WHITE);
        $this->setPoint($board, 16, 3, self::WHITE);
        $this->setPoint($board, 18, 5, self::WHITE);
        $this->setPoint($board, 23, 2, self::BLACK);
        $this->setPoint($board, 12, 5, self::BLACK);
        $this->setPoint($board, 7, 3, self::BLACK);
        $this->setPoint($board, 5, 5, self::BLACK);

        return [
            'board' => $board,
            'bar' => ['1' => 0, '-1' => 0],
            'off' => ['1' => 0, '-1' => 0],
            'turn' => self::WHITE,
            'dice' => [],
            'movesRemaining' => [],
            'winner' => null,
        ];
    }

    private function setPoint(array &$board, int $index, int $count, int $color): void
    {
        $board[$index] = ['count' => $count, 'color' => $color];
    }

    public function roll(array &$state): array
    {
        if ($state['winner'] !== null) throw new DomainException('بازی تمام شده است.');
        if ($state['movesRemaining']) throw new DomainException('اول تاس‌های قبلی را بازی کن.');

        $dice = [random_int(1, 6), random_int(1, 6)];
        $state['dice'] = $dice;
        $state['movesRemaining'] = $dice[0] === $dice[1]
            ? [$dice[0], $dice[0], $dice[0], $dice[0]]
            : $dice;

        if (!$this->hasAnyMove($state)) {
            $state['movesRemaining'] = [];
            $state['turn'] *= -1;
            return ['dice' => $dice, 'passed' => true];
        }
        return ['dice' => $dice, 'passed' => false];
    }

    public function legalMoves(array $state, int|string $from): array
    {
        $color = (int)$state['turn'];
        if (!$state['movesRemaining']) return [];
        if ($state['bar'][(string)$color] > 0 && $from !== 'bar') return [];

        if ($from === 'bar') {
            $start = $color === self::WHITE ? -1 : 24;
        } else {
            if (!is_numeric($from)) return [];
            $from = (int)$from;
            if ($from < 0 || $from > 23 || $state['board'][$from]['color'] !== $color) return [];
            $start = $from;
        }

        $moves = [];
        foreach (array_values(array_unique($state['movesRemaining'])) as $die) {
            $die = (int)$die;
            $target = $start + ($die * $color);

            if ($this->canBearOff($state, $color)) {
                $beyond = $color === self::WHITE ? $target >= 24 : $target < 0;
                if ($beyond) {
                    $distance = $color === self::WHITE ? 24 - $start : $start + 1;
                    if ($die === $distance || $this->canOvershoot($state, $color, (int)$from)) {
                        $moves[] = ['from' => $from, 'to' => 'off', 'die' => $die];
                    }
                }
            }

            if ($target >= 0 && $target < 24) {
                $point = $state['board'][$target];
                if ($point['color'] === null || $point['color'] === $color || $point['count'] === 1) {
                    $moves[] = ['from' => $from, 'to' => $target, 'die' => $die];
                }
            }
        }
        return $moves;
    }

    private function canOvershoot(array $state, int $color, int $from): bool
    {
        if ($color === self::WHITE) {
            for ($i = 18; $i < $from; $i++) {
                if ($state['board'][$i]['color'] === $color) return false;
            }
        } else {
            for ($i = 5; $i > $from; $i--) {
                if ($state['board'][$i]['color'] === $color) return false;
            }
        }
        return true;
    }

    private function canBearOff(array $state, int $color): bool
    {
        if ($state['bar'][(string)$color] > 0) return false;
        $range = $color === self::WHITE ? range(0, 17) : range(6, 23);
        foreach ($range as $i) {
            if ($state['board'][$i]['color'] === $color) return false;
        }
        return true;
    }

    public function move(array &$state, int|string $from, int|string $to, ?int $die = null): array
    {
        if ($state['winner'] !== null) throw new DomainException('بازی تمام شده است.');

        $chosen = null;
        foreach ($this->legalMoves($state, $from) as $candidate) {
            if ((string)$candidate['to'] === (string)$to &&
                ($die === null || $candidate['die'] === $die)) {
                $chosen = $candidate;
                break;
            }
        }
        if ($chosen === null) throw new DomainException('این حرکت طبق قوانین بازی مجاز نیست.');

        $color = (int)$state['turn'];
        if ($from === 'bar') {
            $state['bar'][(string)$color]--;
        } else {
            $from = (int)$from;
            $state['board'][$from]['count']--;
            if ($state['board'][$from]['count'] === 0) $state['board'][$from]['color'] = null;
        }

        if ($to === 'off') {
            $state['off'][(string)$color]++;
        } else {
            $to = (int)$to;
            $target = &$state['board'][$to];
            if ($target['color'] !== null && $target['color'] !== $color && $target['count'] === 1) {
                $state['bar'][(string)$target['color']]++;
                $target = ['count' => 1, 'color' => $color];
            } elseif ($target['color'] === null) {
                $target = ['count' => 1, 'color' => $color];
            } else {
                $target['count']++;
            }
            unset($target);
        }

        $idx = array_search($chosen['die'], $state['movesRemaining'], true);
        if ($idx !== false) array_splice($state['movesRemaining'], $idx, 1);

        if ($state['off'][(string)$color] === 15) {
            $state['winner'] = $color;
            $state['movesRemaining'] = [];
        } elseif (!$state['movesRemaining'] || !$this->hasAnyMove($state)) {
            $state['movesRemaining'] = [];
            $state['turn'] *= -1;
        }

        return $chosen;
    }

    public function hasAnyMove(array $state): bool
    {
        $color = (int)$state['turn'];
        if ($state['bar'][(string)$color] > 0) return (bool)$this->legalMoves($state, 'bar');
        for ($i = 0; $i < 24; $i++) {
            if ($state['board'][$i]['color'] === $color && $this->legalMoves($state, $i)) return true;
        }
        return false;
    }
}
