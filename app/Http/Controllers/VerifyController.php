<?php

namespace App\Http\Controllers;

use App\Models\Stone;
use Illuminate\Contracts\View\View;

class VerifyController extends Controller
{
    public function __invoke(?string $report = null): View
    {
        $stone = $report === null
            ? null
            : Stone::with(['gemType', 'seller'])
                ->where('report_number', $report)
                ->first();

        return view('pages.verify', [
            'report' => $report,
            'stone' => $stone,
            // Distinguishes "no search yet" from "searched and found nothing".
            'notFound' => $report !== null && $stone === null,
        ]);
    }
}
