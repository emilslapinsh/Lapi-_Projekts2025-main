<?php // Flatpickr CDN stili un pielāgots tumša kalendāra izskats ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css" />
<style>
    .flatpickr-calendar {
        z-index: 60;
        background: rgba(63, 63, 70, 0.96);
        color: #fafafa;
        border: 1px solid rgba(255, 255, 255, 0.18);
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.35);
        border-radius: 16px;
    }

    .flatpickr-alt-input {
        background: rgba(82, 82, 91, 0.55) !important;
        color: #fafafa !important;
    }

    .flatpickr-months .flatpickr-month {
        background: transparent;
        color: #fafafa;
    }

    .flatpickr-months .flatpickr-prev-month svg,
    .flatpickr-months .flatpickr-next-month svg {
        fill: #e4e4e7;
    }

    .flatpickr-months .flatpickr-prev-month:hover svg,
    .flatpickr-months .flatpickr-next-month:hover svg {
        fill: #fafafa;
    }

    .flatpickr-current-month .flatpickr-monthDropdown-months,
    .flatpickr-current-month .numInputWrapper input {
        background: rgba(82, 82, 91, 0.55);
        color: #fafafa;
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.16);
    }

    .flatpickr-weekdays {
        background: transparent;
    }

    .flatpickr-weekday {
        color: rgba(250, 250, 250, 0.72);
    }

    .flatpickr-day {
        border-radius: 12px;
        color: #f4f4f5;
    }

    .flatpickr-day:hover {
        background: rgba(113, 113, 122, 0.45);
        border-color: transparent;
    }

    .flatpickr-day.selected,
    .flatpickr-day.startRange,
    .flatpickr-day.endRange {
        background: #ef4444;
        border-color: #ef4444;
        color: #fff;
    }

    .flatpickr-day.today {
        border-color: rgba(252, 165, 165, 0.75);
    }

    .flatpickr-day.flatpickr-disabled,
    .flatpickr-day.prevMonthDay,
    .flatpickr-day.nextMonthDay {
        color: rgba(244, 244, 245, 0.38);
    }
</style>
