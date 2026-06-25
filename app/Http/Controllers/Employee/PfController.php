<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Hrm\PfAccount;
use App\Models\Hrm\PfContribution;
use Illuminate\Http\Request;

class PfController extends Controller
{
    public function index(Request $request)
    {
        $employee = $request->user('employee')->employee;

        $account = PfAccount::query()
            ->where('employee_id', $employee->id)
            ->where('is_active', true)
            ->first();

        $contributions = collect();

        if ($account) {
            $contributions = PfContribution::query()
                ->where('pf_account_id', $account->id)
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->limit(12)
                ->get();
        }

        return view('employee.pf.index', compact('employee', 'account', 'contributions'));
    }
}
