<?php

namespace App\Http\Controllers;

use App\Services\CandidateImportN8nService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CandidateImportN8nController extends Controller
{
    public function __construct(private readonly CandidateImportN8nService $service)
    {
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file'],
        ]);

        $recruiterId = $request->input('recruiterId');

        $result = $this->service->importViaN8n($request->file('file') , $recruiterId);

        return response()->json([
            'status' => 'success',
            'payload' => $result,
        ]);
    }
}
