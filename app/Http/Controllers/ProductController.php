<?php
namespace App\Http\Controllers;
use App\Contracts\D1ServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
class ProductController extends Controller
{
    public function __construct(private D1ServiceInterface $d1)
    {
    }
    public function index(): View
    {
        try {
            $products = $this->d1->products();
        } catch (\Throwable $e) {
            Log::error('Gagal mengambil data produk D1: ' . $e->getMessage());
            $products = []; // Fallback array kosong agar halaman tetap bisa diakses
        }

        return view('products.index', compact('products'));
    }
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price_per_liter' => 'required|numeric|gt:0',
            'buying_price_per_liter' => 'required|numeric|gt:0',
            'current_stock' => 'required|numeric|min:0',
            'capacity' => 'required|numeric|gt:0',
            'supplier_note' => 'nullable|string|max:2000',
        ]);

        try {
            $this->d1->createProduct([
                'name' => (string) $validated['name'],
                'price_per_liter' => (float) $validated['price_per_liter'],
                'buying_price_per_liter' => (float) $validated['buying_price_per_liter'],
                'current_stock' => (float) $validated['current_stock'],
                'capacity' => (float) $validated['capacity'],
                'supplier_note' => (string) ($validated['supplier_note'] ?? ''),
            ]);
            return redirect()->route('products.index')->with('success', 'Produk berhasil ditambahkan.');
        } catch (\Throwable $e) {
            Log::error('Gagal menambah produk melalui D1 bridge.', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            return back()->withInput()->with('error', 'Gagal menambah produk: ' . $e->getMessage());
        }
    }
    public function update(Request $r, int $product): RedirectResponse
    {
        $data = $r->validate(['name' => 'required|string|max:100', 'price_per_liter' => 'required|numeric|min:1', 'cost_per_liter' => 'required|numeric|min:1', 'current_stock' => 'required|numeric|min:0', 'capacity' => 'required|numeric|gte:current_stock']);
        $this->d1->updateProduct($product, $data);
        return back()->with('success', 'Produk diperbarui.');
    }
    public function destroy(int $product): RedirectResponse
    {
        $this->d1->deleteProduct($product);
        return back()->with('success', 'Produk dihapus.');
    }
}
