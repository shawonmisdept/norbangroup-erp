<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Employee\Concerns\ResolvesPortalEmployee;
use App\Models\Hrm\IssuedLetter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LetterController extends Controller
{
    use ResolvesPortalEmployee;

    public function show(Request $request, IssuedLetter $letter)
    {
        $employee = $this->portalEmployee($request);
        $letter = $this->resolveLetter($employee, $letter);
        $letter->load(['issuer']);

        return view('employee.letters.show', compact('employee', 'letter'));
    }

    public function print(Request $request, IssuedLetter $letter)
    {
        $employee = $this->portalEmployee($request);
        $letter = $this->resolveLetter($employee, $letter);
        $letter->load(['employee.factory', 'employee.department', 'employee.designation', 'issuer']);

        return view('admin.hrm.letters.print', compact('letter'));
    }

    private function resolveLetter(\App\Models\Hrm\Employee $employee, IssuedLetter $letter): IssuedLetter
    {
        if ($letter->employee_id !== $employee->id || $letter->isVoided()) {
            abort(404);
        }

        return $letter;
    }
}
