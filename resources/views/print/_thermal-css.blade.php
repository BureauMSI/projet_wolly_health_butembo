<style>
    .ticket { color: #000; font-family: Arial, Helvetica, sans-serif; line-height: 1.4; font-weight: 600; }
    .ticket-58 { width: 54mm; font-size: 14px; }
    .ticket-80 { width: 72mm; font-size: 16px; }
    .ticket-head { text-align: center; margin-bottom: 8px; }
    .ticket-logo { display: block; margin: 0 auto 5px; max-width: 26mm; max-height: 22mm; object-fit: contain; }
    .ticket-58 .ticket-logo { max-width: 20mm; max-height: 18mm; }
    .ticket-name { font-weight: 800; text-transform: uppercase; font-size: 1.2em; }
    .ticket-slogan { font-style: italic; margin-bottom: 3px; font-weight: 600; }
    .ticket-legal { font-size: 0.95em; margin-top: 4px; font-weight: 600; }
    .doc-title { text-align: center; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; border-top: 2px dashed #000; border-bottom: 2px dashed #000; padding: 6px 0; margin: 8px 0; font-size: 1.15em; }
    .ticket-meta { margin: 0 0 8px; }
    .ticket-meta p { margin: 0 0 4px; }
    .ticket table { width: 100%; }
    .ticket th, .ticket td { padding: 3px 0; font-size: 1em; }
    .ticket th { font-weight: 800; text-transform: uppercase; border-bottom: 2px dashed #000; }
    .ticket td.num, .ticket th.num { text-align: right; white-space: nowrap; }
    .ticket .line { border-top: 2px dashed #000; margin: 8px 0; }
    .ticket .totals p { margin: 0 0 3px; display: flex; justify-content: space-between; gap: 8px; }
    .ticket .grand { font-weight: 800; font-size: 1.2em; margin-top: 4px; }
    .ticket-foot { text-align: center; margin-top: 10px; font-size: 1em; font-weight: 600; }
    .ticket-sign { margin-top: 12px; display: flex; justify-content: space-between; gap: 8px; }
    .ticket-sign div { flex: 1; text-align: center; }
    .ticket-sign .sig { border-top: 1px solid #000; margin-top: 20px; padding-top: 4px; font-size: 0.95em; font-weight: 700; }
    @page { margin: 2mm; }
    @if(($width ?? 80) == 58)
        body { width: 54mm; padding: 2mm; }
        @page { size: 58mm auto; margin: 2mm; }
    @else
        body { width: 74mm; padding: 3mm; }
        @page { size: 80mm auto; margin: 3mm; }
    @endif
</style>
