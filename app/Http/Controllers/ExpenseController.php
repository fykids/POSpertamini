<?php
namespace App\Http\Controllers;
use App\Contracts\D1ServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
class ExpenseController extends Controller
{
    public function __construct(private D1ServiceInterface $d1)
    {
    }
    public function index(): View
    {
        return view('expenses.index', ['expenses' => $this->d1->expenses()]);
    }
    public function store(Request $r): RedirectResponse
    {
        $data = $r->validate(['category' => 'required|string|max:100', 'amount' => 'required|numeric|min:1', 'note' => 'nullable|string|max:1000', 'date' => 'required|date']);
        $data['user_id'] = (int) $r->session()->get('d1_user_id');
        $this->d1->createExpense($data);
        return back()->with('success', 'Pengeluaran dicatat.');
    }
    public function destroy(int $expense): RedirectResponse
    {
        $this->d1->deleteExpense($expense);
        return back()->with('success', 'Pengeluaran dihapus.');
    }
}
