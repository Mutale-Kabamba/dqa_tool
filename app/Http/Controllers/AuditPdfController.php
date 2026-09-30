<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class AuditPdfController extends Controller
{
    public function download(Audit $audit): Response
    {
        $audit->load(['project', 'dimensions']);

        $pdf = Pdf::loadView('pdf.audit-dossier', [
            'audit' => $audit,
        ])->setPaper('a4', 'portrait');

        $filename = "DQA_Dossier_{$audit->audit_code}.pdf";

        return $pdf->download($filename);
    }

    public function stream(Audit $audit): Response
    {
        $audit->load(['project', 'dimensions']);

        $pdf = Pdf::loadView('pdf.audit-dossier', [
            'audit' => $audit,
        ])->setPaper('a4', 'portrait');

        $filename = "DQA_Dossier_{$audit->audit_code}.pdf";

        return $pdf->stream($filename);
    }
}
