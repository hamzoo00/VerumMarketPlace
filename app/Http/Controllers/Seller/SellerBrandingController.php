<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\BrandingRequest;
use App\Models\Seller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * A seller's avatar and store banner.
 *
 * Both collections are single-file, so an upload replaces whatever was
 * there. The storefront is resolved from the session, so a seller can
 * only ever change their own branding.
 */
class SellerBrandingController extends Controller
{
    public function store(BrandingRequest $request): JsonResponse
    {
        $seller = $this->requireStorefront($request);
        $kind = $request->kind();

        $media = $seller
            ->addMediaFromRequest('file')
            ->toMediaCollection(config("verum_media.{$kind}.collection"), config('verum_media.disk'));

        return response()->json([
            'data' => [
                'kind' => $kind,
                'url' => $kind === 'avatar' ? $seller->avatarUrl() : $seller->bannerUrl(),
                'name' => $media->file_name,
            ],
        ], Response::HTTP_CREATED);
    }

    public function destroy(Request $request): JsonResponse
    {
        $seller = $this->requireStorefront($request);

        $kind = $request->validate([
            'kind' => ['required', Rule::in(['avatar', 'banner'])],
        ])['kind'];

        $seller->clearMediaCollection(config("verum_media.{$kind}.collection"));

        return response()->json(['message' => __('Removed.')]);
    }

    private function requireStorefront(Request $request): Seller
    {
        $storefront = $request->user('seller')?->storefront;

        abort_if($storefront === null, Response::HTTP_FORBIDDEN, __('Your storefront is not set up yet.'));

        return $storefront;
    }
}
