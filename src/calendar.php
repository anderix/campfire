<?php

function fetchCalendarEvents(string $url, int $lookaheadDays = 14): array {
    $content = @file_get_contents($url);
    if ($content === false) {
        return [];
    }
    return parseIcal($content, $lookaheadDays);
}

function parseIcal(string $ical, int $lookaheadDays = 14): array {
    $events = [];
    $now = new DateTimeImmutable('today');
    $cutoff = $now->modify("+{$lookaheadDays} days");

    $blocks = preg_split('/BEGIN:VEVENT/', $ical);
    array_shift($blocks);

    foreach ($blocks as $block) {
        $block = explode('END:VEVENT', $block)[0];
        $event = parseIcalEvent($block);
        if ($event === null) {
            continue;
        }

        $eventDate = $event['start'];
        if ($eventDate >= $now && $eventDate <= $cutoff) {
            $events[] = $event;
        }
    }

    usort($events, fn($a, $b) => $a['start'] <=> $b['start']);
    return $events;
}

function parseIcalEvent(string $block): ?array {
    $summary = extractIcalField($block, 'SUMMARY');
    $dtstart = extractIcalField($block, 'DTSTART');
    $dtend = extractIcalField($block, 'DTEND');
    $location = extractIcalField($block, 'LOCATION');
    $description = extractIcalField($block, 'DESCRIPTION');

    if ($summary === null || $dtstart === null) {
        return null;
    }

    $start = parseIcalDate($dtstart);
    if ($start === null) {
        return null;
    }

    $end = $dtend ? parseIcalDate($dtend) : null;

    return [
        'summary' => unescapeIcal($summary),
        'start' => $start,
        'end' => $end,
        'location' => $location ? unescapeIcal($location) : null,
        'description' => $description ? unescapeIcal($description) : null,
    ];
}

function extractIcalField(string $block, string $field): ?string {
    $pattern = '/^' . preg_quote($field, '/') . '[^:]*:(.+?)(?=\r?\n[A-Z]|\r?\n?$)/ms';
    if (preg_match($pattern, $block, $m)) {
        return preg_replace('/\r?\n\s/', '', trim($m[1]));
    }
    return null;
}

function parseIcalDate(string $value): ?DateTimeImmutable {
    $value = trim($value);
    $tz = getDisplayTimezone();

    if (str_ends_with($value, 'Z')) {
        $date = DateTimeImmutable::createFromFormat('Ymd\THis\Z', $value, new DateTimeZone('UTC'));
        if ($date !== false) {
            return $date->setTimezone($tz);
        }
    }

    $formats = ['Ymd\THis', 'Ymd'];
    foreach ($formats as $format) {
        $date = DateTimeImmutable::createFromFormat($format, $value, $tz);
        if ($date !== false) {
            return $date;
        }
    }
    return null;
}

function getDisplayTimezone(): DateTimeZone {
    $tz = getSetting('timezone', 'America/Chicago');
    return new DateTimeZone($tz);
}

function unescapeIcal(string $text): string {
    return str_replace(
        ['\\n', '\\,', '\\;', '\\\\'],
        ["\n", ',', ';', '\\'],
        $text
    );
}

function formatEventDate(DateTimeImmutable $date): string {
    return $date->format('l, F j');
}

function formatEventTime(DateTimeImmutable $date): string {
    $time = $date->format('g:i A');
    return str_replace(':00', '', $time);
}
