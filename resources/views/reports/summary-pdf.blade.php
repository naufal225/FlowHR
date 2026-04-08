<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 24px; }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #0f172a;
            margin: 0;
        }
        h1 {
            margin: 0 0 8px 0;
            font-size: 20px;
        }
        .meta {
            margin-bottom: 12px;
            font-size: 10px;
            color: #334155;
        }
        .meta div {
            margin-bottom: 2px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #94a3b8;
            padding: 6px;
            text-align: left;
            vertical-align: top;
            word-break: break-word;
        }
        th {
            background: #e2e8f0;
            font-weight: 700;
            font-size: 10px;
        }
        td {
            background: #ffffff;
        }
        .empty {
            text-align: center;
            font-style: italic;
            color: #64748b;
        }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">
        <div><strong>Generated At:</strong> {{ $generatedAt }} (Asia/Jakarta)</div>
        <div><strong>Requested By:</strong> {{ $requestedBy }}</div>
        <div><strong>Date Range:</strong> {{ $dateRangeLabel }}</div>
        <div><strong>Status Filter:</strong> {{ $statusLabel }}</div>
    </div>

    <table>
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" class="empty">No data available for selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

