<?php

namespace App\Services;

use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ActivitySanitizer
{
    public function sanitize(mixed $value, string $key = '', int $depth = 0): mixed
    {
        if (preg_match('/password|secret|token|authorization|cookie|recovery|credential|^code$/i', $key)) {
            return '[redacted]';
        }
        if ($depth > 12) {
            return '[nested data omitted]';
        }
        if ($value instanceof UploadedFile) {
            return ['filename' => $value->getClientOriginalName(), 'bytes' => $value->getSize()];
        }
        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }
        if (is_array($value)) {
            $clean = [];
            foreach ($value as $name => $item) {
                $clean[$name] = $this->sanitize($item, (string) $name, $depth + 1);
            }

            return $clean;
        }
        if (is_string($value)) {
            if (str_starts_with($value, 'data:') || preg_match('/^(?:image|sheet_image|screenshot)$|(?:image|file|sheet)_?(?:data|base64|png|content)$/i', $key)) {
                return '[file contents omitted]';
            }
            if (in_array(substr(ltrim($value), 0, 1), ['{', '['], true)) {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    return $this->sanitize($decoded, $key, $depth + 1);
                }
            }

            return Str::limit($value, 4000, ' [truncated]');
        }

        return is_scalar($value) || $value === null ? $value : '[object omitted]';
    }
}
