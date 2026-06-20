<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>TripSplit — {{ $settlement->trip_name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        h2 { font-size: 15px; margin-top: 24px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f3f3f3; }
        .muted { color: #666; font-size: 11px; }
    </style>
</head>
<body>
    <h1>Отчёт по поездке: {{ $settlement->trip_name }}</h1>
    <p class="muted">Расчёт № {{ $settlement->uuid }} · {{ $settlement->created_at?->format('d.m.Y H:i') }}</p>

    <h2>Итоги по участникам</h2>
    <table>
        <thead>
            <tr>
                <th>Участник</th>
                <th>Оплатил (₽)</th>
                <th>Должен (₽)</th>
                <th>Баланс (₽)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($summary['participants'] ?? [] as $participant)
                <tr>
                    <td>{{ $participant['name'] }}</td>
                    <td>{{ number_format($participant['paid_rub'], 2, '.', ' ') }}</td>
                    <td>{{ number_format($participant['owes_rub'], 2, '.', ' ') }}</td>
                    <td>{{ number_format($participant['balance_rub'], 2, '.', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Детализация по валютам</h2>
    @foreach ($summary['participants'] ?? [] as $participant)
        <p><strong>{{ $participant['name'] }}</strong></p>
        <table>
            <thead>
                <tr><th>Вложил</th><th>Услуги (доли)</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        @forelse ($participant['paid_by_currency'] ?? [] as $code => $amount)
                            {{ number_format($amount, 2, '.', ' ') }} {{ $code }}<br>
                        @empty
                            —
                        @endforelse
                    </td>
                    <td>
                        @forelse ($participant['owes_by_currency'] ?? [] as $code => $amount)
                            {{ number_format($amount, 2, '.', ' ') }} {{ $code }}<br>
                        @empty
                            —
                        @endforelse
                    </td>
                </tr>
            </tbody>
        </table>
    @endforeach

    @if (! ($summary['books_balanced'] ?? true))
        <p><strong>Внимание:</strong> сумма оплат по чекам не сходится с долями на
            {{ number_format($summary['unsettled_rub'] ?? 0, 2, '.', ' ') }} ₽
            (допуск ±10% при вводе). Часть долга не распределена между участниками.</p>
    @endif

    <h2>Кто кому переводит (в ₽)</h2>
    <table>
        <thead>
            <tr>
                <th>От кого</th>
                <th>Кому</th>
                <th>Сумма</th>
                <th>Валюта</th>
                <th>Эквивалент ₽</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($summary['transfers'] ?? [] as $transfer)
                <tr>
                    <td>{{ $transfer['from_name'] }}</td>
                    <td>{{ $transfer['to_name'] }}</td>
                    <td>{{ number_format($transfer['amount'], 2, '.', ' ') }}</td>
                    <td>{{ $transfer['currency_code'] }}</td>
                    <td>{{ number_format($transfer['amount_rub'], 2, '.', ' ') }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Все расчёты сведены, переводы не требуются.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Чеки</h2>
    @foreach ($trip['transactions'] ?? [] as $transaction)
        <p><strong>{{ $transaction['description'] ?? 'Без описания' }}</strong>
            — {{ number_format((float) ($transaction['amount'] ?? 0), 2, '.', ' ') }}
            {{ strtoupper($transaction['currency_code'] ?? 'RUB') }}</p>
        <table>
            <thead>
                <tr><th colspan="2">Доли</th></tr>
            </thead>
            <tbody>
                @foreach ($transaction['shares'] ?? [] as $share)
                    @php
                        $participant = collect($trip['participants'] ?? [])->firstWhere('id', $share['participant_id']);
                    @endphp
                    <tr>
                        <td>{{ $participant['name'] ?? ('#'.$share['participant_id']) }}</td>
                        <td>{{ number_format((float) $share['amount'], 2, '.', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <table>
            <thead>
                <tr><th colspan="2">Оплаты</th></tr>
            </thead>
            <tbody>
                @foreach (($transaction['payer_payments'] ?? []) ?: [[
                    'participant_id' => $transaction['payer_id'] ?? null,
                    'amount' => $transaction['amount'] ?? 0,
                    'currency_code' => $transaction['currency_code'] ?? 'RUB',
                ]] as $payment)
                    @php
                        $participant = collect($trip['participants'] ?? [])->firstWhere('id', $payment['participant_id']);
                    @endphp
                    <tr>
                        <td>{{ $participant['name'] ?? ('#'.$payment['participant_id']) }}</td>
                        <td>{{ number_format((float) $payment['amount'], 2, '.', ' ') }} {{ strtoupper($payment['currency_code'] ?? 'RUB') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <br>
    @endforeach
</body>
</html>
