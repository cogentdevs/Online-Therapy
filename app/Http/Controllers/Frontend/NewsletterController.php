<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\StoreNewsletterSubscriptionRequest;
use App\Services\NewsletterSubscriptionService;
use Illuminate\Http\JsonResponse;

class NewsletterController extends Controller
{
    public function store(StoreNewsletterSubscriptionRequest $request, NewsletterSubscriptionService $subscriptions): JsonResponse
    {
        $result = $subscriptions->subscribe($request->string('email')->toString());

        $message = $result['already_subscribed']
            ? 'You are already subscribed to the newsletter.'
            : 'Your newsletter subscription has been saved.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'sync_pending' => ! $result['synced'],
        ]);
    }
}
