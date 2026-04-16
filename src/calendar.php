<?php

function fetchCalendarEvents(string $url, int $lookaheadDays = 14): array {
    $content = @file_get_contents($url);
    if ($content === false) {
        return [];
    }
    return parseIcal($content, $lookaheadDays);
}

function parseIcalAll(string $ical): array {
    $events = [];
    $blocks = preg_split('/BEGIN:VEVENT/', $ical);
    array_shift($blocks);
    foreach ($blocks as $block) {
        $block = explode('END:VEVENT', $block)[0];
        $event = parseIcalEvent($block);
        if ($event !== null) {
            $events[] = $event;
        }
    }
    usort($events, fn($a, $b) => $a['start'] <=> $b['start']);
    return $events;
}

function parseIcal(string $ical, int $lookaheadDays = 14): array {
    $events = [];
    $tz = getDisplayTimezone();
    $now = new DateTimeImmutable('today', $tz);
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
    $url = extractIcalField($block, 'URL');

    if ($summary === null || $dtstart === null) {
        return null;
    }

    $start = parseIcalDate($dtstart);
    if ($start === null) {
        return null;
    }

    $end = $dtend ? parseIcalDate($dtend) : null;

    // Scoutbook encodes all-day events as midnight-to-23:45 in local time.
    $allDay = $start->format('H:i') === '00:00'
           && $end !== null
           && in_array($end->format('H:i'), ['23:45', '00:00'], true)
           && $end > $start;

    $multiDay = $end !== null
             && $end->format('Y-m-d') !== $start->format('Y-m-d');

    return [
        'summary' => unescapeIcal($summary),
        'start' => $start,
        'end' => $end,
        'all_day' => $allDay,
        'multi_day' => $multiDay,
        'location' => $location && stripos($location, 'not specified') === false ? unescapeIcal($location) : null,
        'description' => $description ? unescapeIcal($description) : null,
        'url' => $url,
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

function formatEventDateRange(array $event): string {
    $start = $event['start'];
    $end = $event['end'];
    if (!$event['multi_day']) {
        return $start->format('l, F j');
    }
    if ($start->format('Y') === $end->format('Y')) {
        return $start->format('D, M j') . ' - ' . $end->format('D, M j');
    }
    return $start->format('M j, Y') . ' - ' . $end->format('M j, Y');
}

function formatEventTimeRange(array $event): string {
    if ($event['all_day']) {
        return 'All day';
    }
    if ($event['multi_day']) {
        return '';
    }
    $start = $event['start'];
    $end = $event['end'];
    $startPart = formatTimeOfDay($start);
    if ($end === null) {
        return $startPart;
    }
    $endPart = formatTimeOfDay($end);
    if ($startPart === $endPart) {
        return $startPart;
    }
    if ($start->format('A') === $end->format('A')) {
        $startBare = preg_replace('/ (AM|PM)$/', '', $startPart);
        return $startBare . ' - ' . $endPart;
    }
    return $startPart . ' - ' . $endPart;
}

function formatTimeOfDay(DateTimeImmutable $date): string {
    return str_replace(':00', '', $date->format('g:i A'));
}
