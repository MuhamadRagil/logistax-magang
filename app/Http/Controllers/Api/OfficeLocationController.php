<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OfficeLocation\StoreOfficeLocationRequest;
use App\Http\Requests\OfficeLocation\UpdateOfficeLocationRequest;
use App\Models\OfficeLocation;
use App\Services\OfficeLocationService;
use Illuminate\Http\JsonResponse;

class OfficeLocationController extends Controller
{
    public function __construct(private readonly OfficeLocationService $locations) {}

    public function index(): JsonResponse
    {
        return $this->success($this->locations->all());
    }

    public function store(StoreOfficeLocationRequest $request): JsonResponse
    {
        $location = $this->locations->create($request->validated());

        return $this->success($location, null, 201);
    }

    public function show(OfficeLocation $officeLocation): JsonResponse
    {
        return $this->success($officeLocation);
    }

    public function update(UpdateOfficeLocationRequest $request, OfficeLocation $officeLocation): JsonResponse
    {
        return $this->success($this->locations->update($officeLocation, $request->validated()));
    }

    public function destroy(OfficeLocation $officeLocation): JsonResponse
    {
        $this->locations->deactivate($officeLocation);

        return $this->success(null, 'Lokasi kantor berhasil dinonaktifkan.');
    }
}
