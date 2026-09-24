<?php

namespace App\Support;

/**
 * Status -> (label, text class, background class) lookups for the badges
 * used across the dashboard (intern status, evaluation grade, etc). The
 * colors were picked to match design-reference/*.dc.html exactly — they
 * happen to line up with Tailwind's own amber/green/blue/red/violet 700/100
 * shades, so plain Tailwind utility classes are used instead of custom hexes.
 */
class Badge
{
    private const INTERN_STATUS = [
        'pending' => ['Pending', 'text-amber-700', 'bg-amber-100'],
        'active' => ['Active', 'text-green-700', 'bg-green-100'],
        'completed' => ['Completed', 'text-blue-700', 'bg-blue-100'],
        'failed' => ['Failed', 'text-red-700', 'bg-red-100'],
        'extended' => ['Extended', 'text-violet-700', 'bg-violet-100'],
        'rejected' => ['Rejected', 'text-red-700', 'bg-red-100'],
    ];

    private const GRADE = [
        'A' => ['text-green-700', 'bg-green-100'],
        'B' => ['text-blue-700', 'bg-blue-100'],
        'C' => ['text-amber-700', 'bg-amber-100'],
        'D' => ['text-red-700', 'bg-red-100'],
    ];

    /** @return array{0: string, 1: string, 2: string} [label, textClass, bgClass] */
    public static function internStatus(string $status): array
    {
        return self::INTERN_STATUS[$status] ?? [ucfirst($status), 'text-dash-slate', 'bg-dash-bg'];
    }

    /** @return array{0: string, 1: string} [textClass, bgClass] */
    public static function grade(string $grade): array
    {
        return array_slice(self::GRADE[$grade] ?? ['text-dash-slate', 'bg-dash-bg'], 0, 2);
    }

    /** Deterministic avatar color for a given name, from a small fixed palette. */
    public static function avatarColor(string $seed): string
    {
        $palette = ['#003087', '#00A6C0', '#7C3AED', '#B45309', '#15803D', '#1D4ED8', '#B91C1C', '#0F766E'];

        return $palette[crc32($seed) % count($palette)];
    }

    public static function initials(string $name): string
    {
        $words = array_slice(preg_split('/\s+/', trim($name)), 0, 2);

        return strtoupper(implode('', array_map(fn ($w) => mb_substr($w, 0, 1), $words)));
    }
}
