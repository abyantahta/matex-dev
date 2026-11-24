<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportItemPriceRequest;
use App\Models\Item;
use App\Models\QadItem;
use App\Support\Money;
use App\Support\SpreadsheetBook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ItemPriceController extends Controller
{
    public function index(Request $request): Response
    {
        $items = QadItem::query()
            ->rawMaterial()
            ->active()
            ->search($request->string('q')->toString() ?: null)
            ->orderBy('qad_code')
            ->paginate(30)
            ->withQueryString();

        $prices = Item::query()
            ->whereIn('item_number', $items->pluck('qad_code'))
            ->pluck('price', 'item_number');

        $items->getCollection()->transform(function (QadItem $item) use ($prices) {
            $item->setAttribute('price', $prices[$item->qad_code] ?? null);
            $item->setAttribute('price_display', Money::format($prices[$item->qad_code] ?? null));

            return $item;
        });

        return Inertia::render('Admin/Prices/Index', [
            'items' => $items,
            'filters' => [
                'q' => $request->string('q')->toString(),
            ],
        ]);
    }

    public function update(Request $request, string $qadItem): RedirectResponse
    {
        $validated = $request->validate([
            'price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $master = QadItem::query()->rawMaterial()->where('qad_code', $qadItem)->firstOrFail();
        $item = Item::firstOrCreate(
            ['item_number' => $master->qad_code],
            [
                'description' => $master->description ?: $master->qad_code,
                'uom' => $master->uom ?: 'kg',
                'is_active' => true,
            ]
        );

        $item->update(['price' => $validated['price']]);

        return back()->with('success', "Harga {$master->qad_code} disimpan.");
    }

    public function export(): StreamedResponse
    {
        $prices = Item::query()->pluck('price', 'item_number');
        $rows = QadItem::query()
            ->rawMaterial()
            ->active()
            ->orderBy('qad_code')
            ->get(['qad_code', 'description'])
            ->map(fn (QadItem $item) => [
                $item->qad_code,
                $item->description,
                Money::format($prices[$item->qad_code] ?? null),
            ]);

        return SpreadsheetBook::download('harga-rm.xls', ['item_number', 'description', 'harga'], $rows);
    }

    public function import(ImportItemPriceRequest $request): RedirectResponse
    {
        $rows = SpreadsheetBook::read($request->file('file'));
        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'file' => 'File kosong atau kolom item_number / harga tidak ditemukan.',
            ]);
        }

        $updated = 0;
        foreach ($rows as $row) {
            $code = trim((string) ($row['item_number'] ?? $row['item number'] ?? ''));
            $priceRaw = $row['harga'] ?? $row['price'] ?? null;

            if ($code === '' || $priceRaw === null || trim((string) $priceRaw) === '') {
                continue;
            }

            $master = QadItem::query()->rawMaterial()->where('qad_code', $code)->first();
            if (! $master) {
                continue;
            }

            $item = Item::firstOrCreate(
                ['item_number' => $master->qad_code],
                [
                    'description' => $master->description ?: $master->qad_code,
                    'uom' => $master->uom ?: 'kg',
                    'is_active' => true,
                ]
            );
            $item->update(['price' => $this->parsePrice($priceRaw)]);
            $updated++;
        }

        return back()->with('success', "Import harga selesai ({$updated} item).");
    }

    private function parsePrice(mixed $value): float
    {
        $raw = str_replace([' ', ','], ['', '.'], (string) $value);

        return (float) $raw;
    }
}
