<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Services\ReportBuilder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request, ReportBuilder $reports): View
    {
        return view('member.reports.index', [
            'items' => $reports->memberCatalog(),
        ]);
    }

    public function show(Request $request, string $type, ReportBuilder $reports): View
    {
        $member = $request->user('member');
        $payload = $reports->memberPayload($member, $type, $request->all());

        return view('member.reports.show', $payload);
    }

    public function printA4(Request $request, string $type, ReportBuilder $reports): View
    {
        $member = $request->user('member');
        $payload = $reports->memberPayload($member, $type, $request->all());

        return view('print.report-a4', $payload + [
            'printedBy' => $member->full_name,
            'printedAt' => now(),
        ]);
    }
}
