<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ $number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #101529; margin: 0; }
        .wrap { padding: 48px 56px; }
        .brand { font-size: 20px; font-weight: bold; color: #AD0924; }
        .brand span { color: #101529; }
        .meta { margin-top: 4px; color: #6b7280; font-size: 10px; }
        h1 { font-size: 16px; margin: 36px 0 4px; }
        .docmeta { color: #6b7280; font-size: 10px; margin: 0 0 28px; }
        table.parties { width: 100%; margin-bottom: 32px; }
        table.parties td { vertical-align: top; width: 50%; }
        .label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; margin-bottom: 6px; }
        .party p { margin: 0 0 2px; }
        table.lines { width: 100%; border-collapse: collapse; }
        table.lines th { text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; border-bottom: 1px solid #e5e7eb; padding: 0 0 8px; }
        table.lines th.num, table.lines td.num { text-align: right; }
        table.lines td { padding: 12px 0; border-bottom: 1px solid #f3f4f6; }
        table.totals { width: 40%; margin-left: 60%; margin-top: 16px; border-collapse: collapse; }
        table.totals td { padding: 4px 0; }
        table.totals td.num { text-align: right; }
        table.totals tr.grand td { border-top: 1px solid #101529; font-weight: bold; padding-top: 8px; font-size: 12px; }
        .note { margin-top: 40px; padding: 14px 16px; background: #f8f9fb; border-radius: 6px; color: #6b7280; font-size: 10px; line-height: 1.5; }
        .footer { margin-top: 48px; color: #9ca3af; font-size: 9px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="brand">Studio<span>Match</span></div>
        <p class="meta">studiomatch.nl</p>

        <h1>{{ $title }}</h1>
        <p class="docmeta">
            {{ __('invoice.number') }}: {{ $number }} &nbsp;·&nbsp; {{ __('invoice.date') }}: {{ $date }}
            @if ($reference)
                &nbsp;·&nbsp; {{ __('invoice.reference') }}: {{ $reference }}
            @endif
        </p>

        <table class="parties">
            <tr>
                <td class="party">
                    <div class="label">{{ __('invoice.from') }}</div>
                    @foreach ($seller as $line)
                        <p>{{ $line }}</p>
                    @endforeach
                </td>
                <td class="party">
                    <div class="label">{{ __('invoice.to') }}</div>
                    @foreach ($buyer as $line)
                        <p>{{ $line }}</p>
                    @endforeach
                </td>
            </tr>
        </table>

        <table class="lines">
            <tr>
                <th>{{ __('invoice.description') }}</th>
                <th class="num">{{ __('invoice.amount') }}</th>
            </tr>
            @foreach ($lines as $line)
                <tr>
                    <td>{{ $line['label'] }}</td>
                    <td class="num">€ {{ number_format($line['amount'] / 100, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </table>

        <table class="totals">
            @if ($vat)
                <tr>
                    <td>{{ __('invoice.subtotal') }}</td>
                    <td class="num">€ {{ number_format($vat['excl'] / 100, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>{{ __('invoice.vat', ['rate' => $vat['rate']]) }}</td>
                    <td class="num">€ {{ number_format($vat['vat'] / 100, 2, ',', '.') }}</td>
                </tr>
            @endif
            <tr class="grand">
                <td>{{ __('invoice.total') }}</td>
                <td class="num">€ {{ number_format($total / 100, 2, ',', '.') }}</td>
            </tr>
        </table>

        <div class="note">{{ $note }}</div>

        <p class="footer">{{ __('invoice.footer') }}</p>
    </div>
</body>
</html>
