<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private UserService $service)
    {
    }

    public function createUser(Request $request)
    {
        try {
            $user = $this->service->createUser($request->all());
            return $this->responseJSON([
                'id' => (string) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'companyId' => $user->company_id ? (string) $user->company_id : null,
                'role' => $user->userType ? strtolower($user->userType->name) : null,
                'createdAt' => $user->created_at->toIso8601String(),
            ], 'success', 201);
        } catch (Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }

    public function getAllUsers()
    {
        try {
            return $this->responseJSON($this->service->getAllUsers());
        } catch (Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }

    public function getUsersByCompany($companyId)
    {
        try {
            return $this->responseJSON($this->service->getUsersByCompany((int) $companyId));
        } catch (Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }

    public function deleteUser($id)
    {
        try {
            $this->service->deleteUser((int) $id);
            return $this->responseJSON(null, "success", 204);
        } catch (ModelNotFoundException $e) {
            return $this->responseJSON($e->getMessage(), "failure", 404);
        } catch (Exception $e) {
            return $this->responseJSON($e->getMessage(), "failure", 400);
        }
    }
}

