<?php

namespace App\Http\Controllers;

use App\Exceptions\PhilippineGeographicException;
use App\Services\PhilippineGeographicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PhilippineAddressController extends Controller
{
    public function regions(Request $request, PhilippineGeographicService $geo): JsonResponse|RedirectResponse
    {
        if ($redirect = $this->rejectDocumentNavigation($request)) {
            return $redirect;
        }

        return $this->payload(fn (): array => $geo->regions());
    }

    public function provinces(Request $request, PhilippineGeographicService $geo): JsonResponse|RedirectResponse
    {
        if ($redirect = $this->rejectDocumentNavigation($request)) {
            return $redirect;
        }

        $validated = $request->validate([
            'region_code' => ['required', 'regex:/^\d{9,10}$/'],
        ]);

        return $this->payload(fn (): array => $geo->provinces($validated['region_code']));
    }

    public function cities(Request $request, PhilippineGeographicService $geo): JsonResponse|RedirectResponse
    {
        if ($redirect = $this->rejectDocumentNavigation($request)) {
            return $redirect;
        }

        $validated = $request->validate([
            'province_code' => ['nullable', 'regex:/^\d{9,10}$/', 'required_without:region_code'],
            'region_code' => ['nullable', 'regex:/^\d{9,10}$/', 'required_without:province_code'],
        ]);

        return $this->payload(function () use ($geo, $validated): array {
            if (filled($validated['province_code'] ?? null)) {
                return $geo->citiesByProvince($validated['province_code']);
            }

            return $geo->citiesByRegion($validated['region_code']);
        });
    }

    public function barangays(Request $request, PhilippineGeographicService $geo): JsonResponse|RedirectResponse
    {
        if ($redirect = $this->rejectDocumentNavigation($request)) {
            return $redirect;
        }

        $validated = $request->validate([
            'city_code' => ['required', 'regex:/^\d{9,10}$/'],
        ]);

        return $this->payload(fn (): array => $geo->barangays($validated['city_code']));
    }

    /**
     * @param  callable(): list<array<string, mixed>>  $resolver
     */
    private function payload(callable $resolver): JsonResponse
    {
        try {
            return response()->json(['data' => $resolver()]);
        } catch (PhilippineGeographicException) {
            return response()->json([
                'message' => 'Address lookup is temporarily unavailable. Please try again.',
            ], 503);
        }
    }

    private function rejectDocumentNavigation(Request $request): ?RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            // Native fetch is not treated as XMLHttpRequest. Mark it so Laravel
            // does not store this JSON URL as the form's "previous page".
            $request->headers->set('X-Requested-With', 'XMLHttpRequest');

            return null;
        }

        return redirect()->route('enrollment.create');
    }
}
