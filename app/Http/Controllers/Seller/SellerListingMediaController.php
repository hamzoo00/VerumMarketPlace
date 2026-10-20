<?php

namespace App\Http\Controllers\Seller;

use App\Events\ListingChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\ListingMediaRequest;
use App\Models\Listing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;

/**
 * Images and video for a listing.
 *
 * One file per request, so the browser can report real per-file progress
 * and a single rejected file does not fail the whole batch.
 */
class SellerListingMediaController extends Controller
{
    public function store(ListingMediaRequest $request, Listing $listing): JsonResponse
    {
        $this->authoriseOwnership($request, $listing);

        $kind = $request->kind();
        $config = config("verum_media.{$kind}");

        if ($listing->getMedia($config['collection'])->count() >= $config['max_files']) {
            return response()->json([
                'message' => __('You can attach at most :count :kind.', [
                    'count' => $config['max_files'],
                    'kind' => $kind === 'videos' ? __('videos') : __('images'),
                ]),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $media = $listing
            ->addMediaFromRequest('file')
            ->toMediaCollection($config['collection'], config('verum_media.disk'));

        $this->announce($listing);

        return response()->json(['data' => $this->present($media, $listing)], Response::HTTP_CREATED);
    }

    public function destroy(Request $request, Listing $listing, Media $media): JsonResponse
    {
        $this->authoriseOwnership($request, $listing);

        // A media id from another listing must not be deletable here.
        abort_unless(
            $media->model_type === $listing->getMorphClass() && $media->model_id === $listing->id,
            Response::HTTP_FORBIDDEN,
        );

        $media->delete();

        $this->announce($listing);

        return response()->json(['message' => __('Removed.')]);
    }

    /**
     * Media does not touch the listing row, so the model observer never
     * sees it — but a new photograph changes the card, so say so here.
     */
    private function announce(Listing $listing): void
    {
        if ($listing->isPublished()) {
            event(ListingChanged::put($listing->fresh()));
        }
    }

    /** @return array<string, mixed> */
    private function present(Media $media, Listing $listing): array
    {
        return [
            'id' => $media->id,
            'name' => $media->file_name,
            'size' => $media->size,
            'collection' => $media->collection_name,
            'url' => $media->getUrl(),
            'thumb' => $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl(),
            'mime' => $media->mime_type,
            // The route binds on slug, so pass the model, not its id.
            'delete_url' => route('seller.listings.media.destroy', [$listing, $media]),
        ];
    }

    private function authoriseOwnership(Request $request, Listing $listing): void
    {
        abort_unless(
            $listing->seller_id === $request->user('seller')?->storefront?->id,
            Response::HTTP_FORBIDDEN,
        );
    }
}
