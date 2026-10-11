<?php

date_default_timezone_set('Asia/Manila');

function isValidDate($date)
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function getReportDates()
{
    $from = trim($_GET['from'] ?? '');
    $to = trim($_GET['to'] ?? '');

    $defaultFrom = date('Y-m-01');
    $defaultTo = date('Y-m-d');

    if ($from === '' && $to === '') {
        return [$defaultFrom, $defaultTo, ''];
    }

    if (!isValidDate($from) || !isValidDate($to)) {
        return [$defaultFrom, $defaultTo, 'Please choose a valid From and To date.'];
    }

    if ($from > $to) {
        return [$defaultFrom, $defaultTo, 'The From date cannot be later than the To date.'];
    }

    return [$from, $to, ''];
}