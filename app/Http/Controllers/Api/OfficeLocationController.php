<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OfficeLocation\StoreOfficeLocationRequest;
use App\Http\Requests\OfficeLocation\UpdateOfficeLocationRequest;
use App\Models\OfficeLocation;
use Illuminate\Http\JsonResponse;

class OfficeLocationController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->success(OfficeLocation::orderBy('name')->get());
    }

    public function store(StoreOfficeLocationRequest $request): JsonResponse
    {
        $location = OfficeLocation::create($request->validated());

        return $this->success($location, null, 201);
    }

    public function show(OfficeLocation $officeLocation): JsonResponse
    {
        return $this->success($officeLocation);
    }

    public function update(UpdateOfficeLocationRequest $request, OfficeLocation $officeLocation): JsonResponse
    {
        $officeLocation->update($request->validated());

        return $this->success($officeLocation->fresh());
    }

    public function destroy(OfficeLocation $officeLocation): JsonResponse
    {
        $officeLocation->update(['is_active' => false]);

        return $this->success(null, 'Lokasi kantor berhasil dinonaktifkan.');
    }
}
