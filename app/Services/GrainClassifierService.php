<?php

namespace App\Services;

use App\Models\RiceVariety;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GrainClassifierService
{
    /**
     * @return array{variety: ?RiceVariety, confidence: float, raw: array<string, mixed>}
     */
    public function classify(string $path): array
    {
        $data = Http::timeout(config('services.grain.timeout'))
            ->attach('file', (string) file_get_contents($path), basename($path))
            ->post(config('services.grain.url').'/predict')
            ->throw()
            ->json();

        $prediction = $data['data']['prediction'] ?? null;
        $className = $prediction['class_name'] ?? null;

        return [
            'variety' => $className
                ? RiceVariety::all()->first(
                    fn (RiceVariety $v) => Str::slug($v->name, '_') === Str::slug($className, '_')
                )
                : null,
            'confidence' => ((float) ($prediction['confidence'] ?? 0)) / 100,
            'raw' => $data ?? [],
        ];
    }
}
