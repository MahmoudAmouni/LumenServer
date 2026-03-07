<?php

namespace App\Http\Controllers;

use App\Http\Requests\CandidateImportRequest;
use App\Services\CandidateImportN8nService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CandidateImportN8nController extends Controller{
    public function __construct(private readonly CandidateImportN8nService $service){}

    public function import(CandidateImportRequest $request){
        $validatedData = $request->validated([
            'file' => ['required', 'file'],
            'recruiterId' => ['required', 'integer'],
            'jobId' => ['required', 'integer'],
        ]);
        $result = $this->service->importViaN8n($validatedData['file'], $validatedData["recruiterId"], $validatedData['jobId']);

        return response()->json([
            'status' => 'success',
            'payload' => $result,
        ]);
    }
}
