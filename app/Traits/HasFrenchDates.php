<?php

namespace App\Traits;

use Carbon\Carbon;

trait HasFrenchDates
{
    private static $frenchDays = [
        'Monday' => 'lundi',
        'Tuesday' => 'mardi', 
        'Wednesday' => 'mercredi',
        'Thursday' => 'jeudi',
        'Friday' => 'vendredi',
        'Saturday' => 'samedi',
        'Sunday' => 'dimanche'
    ];

    private static $frenchMonths = [
        'January' => 'janvier',
        'February' => 'février',
        'March' => 'mars',
        'April' => 'avril',
        'May' => 'mai',
        'June' => 'juin',
        'July' => 'juillet',
        'August' => 'août',
        'September' => 'septembre',
        'October' => 'octobre',
        'November' => 'novembre',
        'December' => 'décembre'
    ];

    public function formatFrenchDate($date): string
    {
        if (!$date) {
            return '';
        }

        if (!($date instanceof Carbon)) {
            $date = Carbon::parse($date);
        }
        
        $dayName = self::$frenchDays[$date->format('l')] ?? $date->format('l');
        $monthName = self::$frenchMonths[$date->format('F')] ?? $date->format('F');
        
        return sprintf(
            '%s %d %s %d à %s',
            $dayName,
            $date->day,
            $monthName,
            $date->year,
            $date->format('H\hi')
        );
    }

    public function formatFrenchDateOnly($date): string
    {
        if (!$date) {
            return '';
        }

        if (!($date instanceof Carbon)) {
            $date = Carbon::parse($date);
        }
        
        $dayName = self::$frenchDays[$date->format('l')] ?? $date->format('l');
        $monthName = self::$frenchMonths[$date->format('F')] ?? $date->format('F');
        
        return sprintf(
            '%s %d %s %d',
            $dayName,
            $date->day,
            $monthName,
            $date->year
        );
    }

    public function formatShortFrenchDate($date): string
    {
        if (!$date) {
            return '';
        }

        if (!($date instanceof Carbon)) {
            $date = Carbon::parse($date);
        }
        
        $monthName = self::$frenchMonths[$date->format('F')] ?? $date->format('F');
        
        return sprintf(
            '%d %s %d',
            $date->day,
            substr($monthName, 0, 3),
            $date->year
        );
    }

    public function getCreatedAtFrenchAttribute(): string
    {
        return $this->formatFrenchDate($this->created_at);
    }

    public function getUpdatedAtFrenchAttribute(): string
    {
        return $this->formatFrenchDate($this->updated_at);
    }
}