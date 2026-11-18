<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        .header { border-bottom: 2px solid #0f172a; padding-bottom: 8px; margin-bottom: 14px; }
        .title { font-size: 16px; font-weight: bold; }
        .sub { font-size: 11px; color: #475569; }
        .summary-box { margin-bottom: 14px; }
        .summary-item { display: inline-block; margin-right: 24px; background: #f8fafc; border: 1px solid #cbd5e1; padding: 6px 10px; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #0f172a; color: #ffffff; padding: 7px 8px; text-align: left; font-size: 10px; }
        td { border: 1px solid #cbd5e1; padding: 6px 8px; }
        tr:nth-child(even) td { background: #f8fafc; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">PERCETAKAN MIDAD AL-ILMI — {{ $title }}</div>
        <div class="sub">Periode: {{ $periodLabel }} | Dicetak pada: {{ now()->format('d/m/Y H:i') }} WIB</div>
    </div>

    <div class="summary-box">
        @foreach($summary as $item)
            <div class="summary-item">
                <div style="font-size: 10px; color: #64748b;">{{ $item['label'] }}</div>
                <div style="font-size: 13px; font-weight: bold;">{{ $item['value'] }}</div>
            </div>
        @endforeach
    </div>

    <table>
        <thead>
            <tr>
                @foreach($headings as $h)
                    <th>{{ $h }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ is_float($cell) ? number_format($cell, 0, ',', '.') : $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headings) }}" style="text-align: center; padding: 16px;">Tidak ada data untuk periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

