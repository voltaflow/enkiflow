<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Session;

class RelativeDate
{
    /**
     * Fecha de anclaje para fechas relativas.
     *
     * @var Carbon|null
     */
    protected static $anchorDate = null;

    /**
     * Establecer la fecha de anclaje.
     *
     * @param string|Carbon|null $date
     * @return void
     */
    public static function setAnchor($date = null): void
    {
        if ($date instanceof Carbon) {
            static::$anchorDate = $date;
        } elseif (is_string($date)) {
            static::$anchorDate = Carbon::parse($date);
        } else {
            static::$anchorDate = Carbon::now();
        }
        
        Session::put('demo_anchor_date', static::$anchorDate);
    }

    /**
     * Obtener la fecha de anclaje.
     *
     * @return Carbon
     */
    public static function getAnchor(): Carbon
    {
        if (static::$anchorDate === null) {
            static::$anchorDate = Session::get('demo_anchor_date', Carbon::now());
        }
        
        return static::$anchorDate;
    }

    /**
     * Obtener una fecha relativa a la fecha de anclaje.
     *
     * @param string $min Modificador mínimo (ej. '-1 month')
     * @param string $max Modificador máximo (ej. '+1 month')
     * @param Carbon|null $reference Fecha de referencia (opcional)
     * @return Carbon
     */
    public static function get(string $min, string $max, ?Carbon $reference = null): Carbon
    {
        $anchor = $reference ?? static::getAnchor();
        
        if ($min === $max) {
            return (clone $anchor)->modify($min);
        }
        
        $minDate = (clone $anchor)->modify($min);
        $maxDate = (clone $anchor)->modify($max);
        
        return Carbon::createFromTimestamp(
            fake()->dateTimeBetween($minDate, $maxDate)->getTimestamp()
        );
    }

    /**
     * Resetear la fecha de anclaje.
     *
     * @return void
     */
    public static function reset(): void
    {
        static::$anchorDate = null;
        Session::forget('demo_anchor_date');
    }
}