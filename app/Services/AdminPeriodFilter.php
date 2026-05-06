<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Session;

class AdminPeriodFilter
{
    public const SESSION_KEY = 'admin_period';
    public const SESSION_START_KEY = 'admin_period_start';
    public const SESSION_END_KEY = 'admin_period_end';
    public const DEFAULT_PERIOD = '30d';

    public const VALID_PERIODS = ['30d', '60d', '90d', 'ano', 'tudo', 'personalizado'];

    public function getPeriod(): string
    {
        $period = Session::get(self::SESSION_KEY, self::DEFAULT_PERIOD);
        return in_array($period, self::VALID_PERIODS, true) ? $period : self::DEFAULT_PERIOD;
    }

    public function getStart(): ?Carbon
    {
        return match ($this->getPeriod()) {
            '30d' => now()->subDays(30)->startOfDay(),
            '60d' => now()->subDays(60)->startOfDay(),
            '90d' => now()->subDays(90)->startOfDay(),
            'ano' => now()->startOfYear(),
            'personalizado' => Session::get(self::SESSION_START_KEY)
                ? Carbon::parse(Session::get(self::SESSION_START_KEY))->startOfDay()
                : null,
            'tudo' => null,
            default => null,
        };
    }

    public function getEnd(): ?Carbon
    {
        return match ($this->getPeriod()) {
            '30d' => now()->addDays(30)->endOfDay(),
            '60d' => now()->addDays(60)->endOfDay(),
            '90d' => now()->addDays(90)->endOfDay(),
            'ano' => now()->endOfYear(),
            'personalizado' => Session::get(self::SESSION_END_KEY)
                ? Carbon::parse(Session::get(self::SESSION_END_KEY))->endOfDay()
                : null,
            default => null,
        };
    }

    public function setPeriod(string $period, ?string $start = null, ?string $end = null): void
    {
        if (! in_array($period, self::VALID_PERIODS, true)) {
            $period = self::DEFAULT_PERIOD;
        }

        Session::put(self::SESSION_KEY, $period);

        if ($period === 'personalizado') {
            Session::put(self::SESSION_START_KEY, $start);
            Session::put(self::SESSION_END_KEY, $end);
        } else {
            Session::forget(self::SESSION_START_KEY);
            Session::forget(self::SESSION_END_KEY);
        }
    }

    public function isFiltering(): bool
    {
        return $this->getPeriod() !== 'tudo';
    }

    public function isDefault(): bool
    {
        return $this->getPeriod() === self::DEFAULT_PERIOD;
    }

    /**
     * Aplica o filtro de período numa coluna de data (ex: events.date).
     */
    public function applyToDate(Builder|QueryBuilder $query, string $column = 'date'): void
    {
        $start = $this->getStart();
        $end = $this->getEnd();

        if ($start) {
            $query->where($column, '>=', $start);
        }
        if ($end) {
            $query->where($column, '<=', $end);
        }
    }

    /**
     * Aplica o filtro nos eventos relacionados a uma inscrição/notificação via event_id.
     * Usa whereHas('event') ou um whereIn de event_ids.
     */
    public function applyToInscriptionsViaEvent(Builder $query): void
    {
        if (! $this->isFiltering()) {
            return;
        }

        $query->whereHas('event', function ($q) {
            $this->applyToDate($q, 'date');
        });
    }

    /**
     * Aplica o filtro em notifications.created_at.
     */
    public function applyToCreatedAt(Builder|QueryBuilder $query, string $column = 'created_at'): void
    {
        $this->applyToDate($query, $column);
    }

    /**
     * Opções do dropdown — label legível.
     */
    public function getOptions(): array
    {
        return [
            '30d' => '30 dias antes e depois',
            '60d' => '60 dias antes e depois',
            '90d' => '90 dias antes e depois',
            'ano' => 'Este ano',
            'tudo' => 'Tudo',
            'personalizado' => 'Personalizado',
        ];
    }

    public function getLabel(): string
    {
        return $this->getOptions()[$this->getPeriod()] ?? 'Período';
    }

    /**
     * Texto descritivo curto do range (para exibir ao lado do dropdown).
     */
    public function getRangeText(): string
    {
        $start = $this->getStart();
        $end = $this->getEnd();

        if (! $start && ! $end) {
            return 'sem limite de data';
        }

        if ($start && ! $end) {
            return 'a partir de '.$start->format('d/m/Y');
        }

        if (! $start && $end) {
            return 'até '.$end->format('d/m/Y');
        }

        return $start->format('d/m/Y').' até '.$end->format('d/m/Y');
    }

    public function getStartString(): ?string
    {
        return Session::get(self::SESSION_START_KEY);
    }

    public function getEndString(): ?string
    {
        return Session::get(self::SESSION_END_KEY);
    }
}
