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

        $recruiterId = 1;//change it bas feek

        if (!$recruiterId) {
            return response()->json([
                'status' => 'failure',
                'payload' => [
                    'message' => 'Unauthenticated',
                ],
            ], 401);
        }

        $result = $this->service->importViaN8n($request->file('file'));

        return response()->json([
            'status' => 'success',
            'payload' => $result,
        ]);
    }
}
