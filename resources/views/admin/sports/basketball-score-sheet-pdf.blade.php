<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @php($landscape = ($orientation ?? 'portrait') === 'landscape')
        @page { margin: 0; size: A4 {{ $landscape ? 'landscape' : 'portrait' }}; }
        body { margin: 0; padding: 0; font-size: 0; line-height: 0; }
        img { position: absolute; top: 0; left: 0; width: {{ $landscape ? '297mm' : '210mm' }}; height: {{ $landscape ? '210mm' : '297mm' }}; }
    </style>
</head>
<body>
    <img src="{{ $previewDataUri }}" alt="Completed score sheet">
</body>
</html>
