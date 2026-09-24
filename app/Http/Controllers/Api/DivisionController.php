<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Division\StoreDivisionRequest;
use App\Http\Requests\Division\UpdateDivisionRequest;
use App\Models\Division;
use Illuminate\Http\JsonResponse;

class DivisionController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->success(Division::orderBy('name')->get());
    }

    public function store(StoreDivisionRequest $request): JsonResponse
    {
        $division = Division::create($request->validated());

        return $this->success($division, null, 201);
    }

    public function show(Division $division): JsonResponse
    {
        return $this->success($division);
    }

    public function update(UpdateDivisionRequest $request, Division $division): JsonResponse
    {
        $division->update($request->validated());

        return $this->success($division->fresh());
    }

    public function destroy(Division $division): JsonResponse
    {
        if ($division->interns()->exists()) {
            return $this->error('Divisi masih digunakan oleh intern, tidak bisa dihapus.', 400);
        }

        $division->update(['is_active' => false]);

        return $this->success(null, 'Divisi berhasil dinonaktifkan.');
    }
}
