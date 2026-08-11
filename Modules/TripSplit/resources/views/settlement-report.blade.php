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
        .note { background: #f8f8f8; border-left: 3px solid #888; padding: 8px 10px; font-size: 11px; margin: 12px 0 16px; }
    </style>
</head>
<body>
    <h1>Отчёт по поездке: {{ $settlement->trip_name }}</h1>
    <p class="muted">Расчёт № {{ $settlement->uuid }} · {{ $settlement->created_at?->format('d.m.Y H:i') }}</p>
    @if (!empty($summary['calculation_note']))
        <p class="note">{{ $summary['calculation_note'] }}</p>
    @endif

    <h2>Итоги по участникам</h2>
    <table>
        <thead>
            <tr>
                <th>Участник</th>
                <th>Оплатил (₽)</th>
                <th>Потребление (₽)</th>
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
                <tr><th>Вложил</th><th>Потребление</th></tr>
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

    <h2>Чеки и расчёт потребления</h2>
    @foreach ($summary['transactions'] ?? [] as $transaction)
        <p>
            <strong>{{ $transaction['description'] ?: 'Без описания' }}</strong>
        </p>
        <p class="muted">
            Сумма чека: {{ number_format((float) ($transaction['receipt_amount'] ?? 0), 2, '.', ' ') }}
            {{ $transaction['currency_code'] ?? 'RUB' }}
            · Доли по чеку: {{ number_format((float) ($transaction['shares_total_receipt'] ?? 0), 2, '.', ' ') }}
            {{ $transaction['currency_code'] ?? 'RUB' }}
            · Оплачено: {{ number_format((float) ($transaction['paid_in_receipt_currency'] ?? 0), 2, '.', ' ') }}
            {{ $transaction['currency_code'] ?? 'RUB' }}
            ({{ number_format((float) ($transaction['paid_total_rub'] ?? 0), 2, '.', ' ') }} ₽)
        </p>
        <table>
            <thead>
                <tr>
                    <th>Участник</th>
                    <th>Доля по чеку</th>
                    <th>Доля, %</th>
                    <th>Потребление (₽)</th>
                    <th>Потребление (валюта чека)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($transaction['consumption'] ?? [] as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ number_format((float) $row['share_receipt'], 2, '.', ' ') }}</td>
                        <td>{{ number_format((float) $row['share_percent'], 2, '.', ' ') }}%</td>
                        <td>{{ number_format((float) $row['consumption_rub'], 2, '.', ' ') }}</td>
                        <td>{{ number_format((float) $row['consumption_receipt'], 2, '.', ' ') }} {{ $transaction['currency_code'] ?? 'RUB' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">Нет долей для расчёта.</td></tr>
                @endforelse
            </tbody>
        </table>
        <table>
            <thead>
                <tr><th colspan="2">Кто оплатил</th></tr>
            </thead>
            <tbody>
                @forelse ($transaction['payer_payments'] ?? [] as $payment)
                    <tr>
                        <td>{{ $payment['name'] ?? ('#'.$payment['participant_id']) }}</td>
                        <td>{{ number_format((float) $payment['amount'], 2, '.', ' ') }} {{ $payment['currency_code'] ?? 'RUB' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2">—</td></tr>
                @endforelse
            </tbody>
        </table>
        <br>
    @endforeach
    @if (empty($summary['transactions']))
        @foreach ($trip['transactions'] ?? [] as $transaction)
            <p><strong>{{ $transaction['description'] ?? 'Без описания' }}</strong>
                — {{ number_format((float) ($transaction['amount'] ?? 0), 2, '.', ' ') }}
                {{ strtoupper($transaction['currency_code'] ?? 'RUB') }}</p>
        @endforeach
    @endif
</body>
</html>
