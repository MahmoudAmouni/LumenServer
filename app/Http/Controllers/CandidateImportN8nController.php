<?php

namespace App\Http\Controllers;

use App\Http\Requests\CandidateImportRequest;
use App\Services\CandidateImportN8nService;

class CandidateImportN8nController extends Controller{
    public function __construct(private readonly CandidateImportN8nService $service){}

    public function import(CandidateImportRequest $request){
        try{
            $validatedData = $request->validated();
            $result = $this->service->importViaN8n($validatedData['file'], $validatedData["recruiterId"], $validatedData['jobId']);
            return response()->json([
                'status' => 'success',
                'payload' => $result,
            ]);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'failure',
                'message' => $e->getMessage(),
            ], 400);
        }
        
    }
}
