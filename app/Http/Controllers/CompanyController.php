<?php

namespace App\Http\Controllers;

use App\Services\CompanyService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function __construct(private CompanyService $service)
    {
    }

    public function createCompany(Request $request)
    {
        try {
            $company = $this->service->createCompany($request->all());
            return $this->responseJSON([
                'id' => (string) $company->id,
                'name' => $company->name,
                'createdAt' => $company->created_at->toIso8601String(),
            ], 'success', 201);
        } catch (Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }

    public function getAllCompanies()
    {
        try {
            return $this->responseJSON($this->service->getAllCompanies());
        } catch (Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }

    public function deleteCompany($id)
    {
        try {
            $this->service->deleteCompany((int) $id);
            return $this->responseJSON(null, "success", 204);
        } catch (ModelNotFoundException $e) {
            return $this->responseJSON($e->getMessage(), "failure", 404);
        } catch (Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }
}

