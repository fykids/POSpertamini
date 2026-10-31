<?php
namespace App\Http\Controllers;
use App\Contracts\D1ServiceInterface; use Illuminate\Contracts\View\View; use Illuminate\Http\Request;
class FinancialReportController extends Controller { public function __construct(private D1ServiceInterface $d1) {} public function index(Request $r): View { $period=$r->string('period','month')->toString(); return view('reports.financial',['period'=>$period,'report'=>$this->d1->financialReport($period)]); } }
