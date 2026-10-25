<?php

function badWordList(): array
{
    return [
        // English
        'fuck', 'fucking', 'fucker', 'shit', 'bullshit', 'bitch', 'asshole', 'bastard', 'damn',
        'crap', 'dick', 'piss', 'slut', 'whore', 'idiot', 'stupid', 'moron',
        // Filipino
        'putang ina', 'putangina', 'nakakatangina', 'puta', 'tangina', 'tang ina', 'gago', 'gaga', 'tanga', 'bobo',
        'ulol', 'tarantado', 'leche', 'punyeta', 'pakyu', 'pakshet', 'siraulo', 'buwisit',
        'bwisit', 'lintik', 'inutil', 'hayop ka', 'peste', 'pesteng yawa', 'animal ka', 'burat', 'pukinang ina',
    ];
}

function badWordPattern(): string
{
    static $pattern = null;
    if ($pattern !== null) {
        return $pattern;
    }

    $lookAlike = ['a' => '[a4]', 'e' => '[e3]', 'i' => '[i1]', 'o' => '[o0]', 's' => '[s5]'];
    $words = badWordList();
    usort($words, function ($a, $b) {          
        return strlen($b) - strlen($a);
    });

    $parts = [];
    foreach ($words as $word) {
        $p = '';
        foreach (str_split(strtolower($word)) as $ch) {
            if ($ch === ' ') {
                $p .= '\s+';
            } else {
                $p .= ($lookAlike[$ch] ?? preg_quote($ch, '/')) . '+';
            }
        }
        $parts[] = $p;
    }

    $pattern = '/\b(?:' . implode('|', $parts) . ')\b/iu';
    return $pattern;
}

function maskBadWords(string $text): string
{
    $masked = preg_replace_callback(
        badWordPattern(),
        function (array $m) {
            return str_repeat('*', mb_strlen($m[0]));
        },
        $text
    );
    return $masked ?? $text;   