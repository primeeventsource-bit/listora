<?php

namespace App\Enums;

/**
 * Audience tag for help articles. Mirrors Listora's two personas so search
 * results, the help index, and the chat agent's search tool can scope by
 * who is asking.
 */
enum HelpAudience: string
{
    case Traveler = 'traveler';
    case Owner = 'owner';
    case All = 'all';

    public function label(): string
    {
        return match ($this) {
            self::Traveler => 'Travelers & buyers',
            self::Owner => 'Owners advertising a listing',
            self::All => 'Everyone',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
