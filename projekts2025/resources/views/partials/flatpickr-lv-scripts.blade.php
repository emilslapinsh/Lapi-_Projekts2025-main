<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/lv.js"></script>
<script>
    // Meklē ar klasi js-flatpickr un uzliek vienotu Latvijas datumu formātu
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof flatpickr === 'undefined') return;

        // Alt ievades laukuma Tailwind klase, lai sakrīt ar pārējām formām
        var altCls =
            'mt-2 w-full rounded-xl bg-zinc-950/60 px-4 py-3 text-base text-zinc-100 ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-red-500/50';

        document.querySelectorAll('input.js-flatpickr').forEach(function (el) {
            flatpickr(el, {
                locale: flatpickr.l10ns.lv,
                dateFormat: 'Y-m-d', // Nosūtīt serverim ISO formātā
                altInput: true,
                altFormat: 'd.m.Y', // Rādīt lietotājam ar punktiem
                allowInput: true,
                clickOpens: true,
                disableMobile: true, // Lai nevienotu ar OS mini kalendāru
                appendTo: document.body, // Overlay virs sarežģītās izkārtojumu shēmas
                static: false,
                monthSelectorType: 'static',
                altInputClass: altCls,
            });
        });
    });
</script>
