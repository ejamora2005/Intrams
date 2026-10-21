<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $edition?->name ?? 'Intramurals' }} - Declared results</title>
    <style>
        @page { margin: 28px 30px; }
        body { color: #172b43; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        h1 { color: #0c3555; font-size: 18px; margin: 0 0 3px; }
        h2 { color: #0c3555; font-size: 12px; margin: 18px 0 7px; }
        p { margin: 2px 0; }
        .meta { color: #52677c; margin-bottom: 10px; }
        table { border-collapse: collapse; width: 100%; }
        th { background: #0c3555; color: #fff; font-weight: bold; }
        th, td { border: 1px solid #b8c7d5; padding: 5px 6px; text-align: left; vertical-align: top; }
        tr:nth-child(even) td { background: #f1f6fa; }
        .number { text-align: right; white-space: nowrap; }
        .empty { color: #52677c; font-style: italic; }
        .footer { color: #52677c; font-size: 8px; margin-top: 15px; }
    </style>
</head>
<body>
    <h1>{{ $edition?->name ?? 'Intramurals' }} - Declared results</h1>
    <p class="meta">School year: {{ $edition?->school_year ?? '-' }} | Declared events: {{ $declared_count }} | Generated: {{ $generated_at }}</p>

    <h2>Overall standings</h2>
    <table>
        <thead><tr><th>Rank</th><th>Faction</th><th>Points</th><th>Gold</th><th>Silver</th><th>Bronze</th></tr></thead>
        <tbody>
        @forelse ($standings as $row)
            <tr>
                <td class="number">{{ $row['rank'] }}</td>
                <td>{{ $row['team']->name }}</td>
                <td class="number">{{ number_format((float) $row['tally']->points, 2) }}</td>
                <td class="number">{{ $row['tally']->gold_count }}</td>
                <td class="number">{{ $row['tally']->silver_count }}</td>
                <td class="number">{{ $row['tally']->bronze_count }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">No active factions are available for this edition.</td></tr>
        @endforelse
        </tbody>
    </table>

    <h2>Declared event placements</h2>
    <table>
        <thead><tr><th>Category</th><th>Event</th><th>Declared at (PHT)</th><th>Declared by</th><th>Placement</th><th>Label</th><th>Faction</th><th>Points</th></tr></thead>
        <tbody>
        @forelse ($results as $result)
            @foreach ($result['placements'] as $placement)
                <tr>
                    <td>{{ $result['category_label'] }}</td>
                    <td>{{ $result['name'] }}</td>
                    <td>{{ $result['declared_at']?->copy()->setTimezone('Asia/Manila')->format('M j, Y g:i:s A') ?? 'Time unavailable' }}</td>
                    <td>{{ $result['declared_by'] }}</td>
                    <td class="number">{{ $placement['placement'] }}</td>
                    <td>{{ $placement['label'] }}</td>
                    <td>{{ $placement['team_name'] }}</td>
                    <td class="number">{{ number_format((float) $placement['points'], 2) }}</td>
                </tr>
            @endforeach
        @empty
            <tr><td colspan="8" class="empty">No declared event results are available.</td></tr>
        @endforelse
        </tbody>
    </table>
    <p class="footer">This report is a read-only export of the declared results and standings saved in the system.</p>
</body>
</html>
