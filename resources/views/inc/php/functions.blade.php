<?php
function convert_date($date) {
    $dateTime = DateTime::createFromFormat(DateTimeInterface::ISO8601, $date);
    return $dateTime->format('d.m.Y h:i:s');
}
?>


