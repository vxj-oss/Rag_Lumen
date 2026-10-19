<?php

namespace App\Http\Controllers\Web\Employees;

use App\Http\Controllers\Controller;
use App\Models\EmployeeSanction;
use App\Support\ActivityLogger;
use App\Support\CompanySignature;
use App\Support\ListPagination;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class EmployeeRecordController extends Controller
{
    public function mine(Request $request)
    {
        $employee = $request->user()->employee ?? abort(404, 'Tu cuenta no tiene un trabajador vinculado.');

        return view('employees.record', ['employee' => $employee, 'sanctions' => EmployeeSanction::where('empleado_id', $employee->id)->latest()->paginate(ListPagination::size())->withQueryString()]);
    }

    public function download(EmployeeSanction $sanction)
    {
        $this->authorize('view', $sanction->employee);

        return Pdf::loadView('reports.employee-breach-notice', ['employee' => $sanction->employee, 'generatedAt' => $sanction->created_at,
            'fechaHechos' => $sanction->fecha_hechos, 'hechos' => $sanction->hechos, 'clausulaInfringida' => $sanction->clausula_infringida,
            'monto' => $sanction->monto, 'empresaFirma' => CompanySignature::base64()])->download('sancion-'.$sanction->id.'.pdf');
    }

    public function update(Request $request, EmployeeSanction $sanction)
    {
        $this->authorize('update', $sanction->employee);
        $data = $request->validate(['hechos' => ['required', 'string', 'max:3000'], 'fecha_hechos' => ['required', 'date'], 'clausula_infringida' => ['nullable', 'string', 'max:255'], 'monto' => ['nullable', 'numeric', 'min:0', 'max:999999.99']]);
        $before = $sanction->getAttributes();
        $sanction->update($data);
        ActivityLogger::recordUpdate($sanction, $before, 'sanción');

        return back()->with('status', 'Sanción actualizada.');
    }
}
