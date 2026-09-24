<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request, ReportBuilder $reports): View
    {
        $user = $request->user();
        abort_unless($reports->catalog($user) !== [], 403);

        return view('admin.reports.index', [
            'items' => $reports->catalog($user),
        ]);
    }

    public function show(Request $request, string $type, ReportBuilder $reports): View
    {
        $payload = $reports->payload($request->user(), $type, $request->all());

        return view('admin.reports.show', $payload);
    }

    public function printA4(Request $request, string $type, ReportBuilder $reports): View
    {
        $payload = $reports->payload($request->user(), $type, $request->all());

        return view('print.report-a4', $payload + [
            'printedBy' => $request->user()->name,
            'printedAt' => now(),
        ]);
    }

    public function printLegacy(Request $request): RedirectResponse
    {
        $type = $request->input('type', 'summary');

        return redirect()->route('admin.reports.print', array_merge($request->query(), ['type' => $type]));
    }
}
