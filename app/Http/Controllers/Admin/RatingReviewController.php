<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RatingReview;
use App\Traits\SearchEscaping;
use Illuminate\Http\Request;

class RatingReviewController extends Controller
{
    use SearchEscaping;
    public function index(Request $request)
    {
        $query = RatingReview::with(['user', 'order']);

        if ($request->filled('search')) {
            $search = $this->escapeLikePattern($request->search);
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('min_rating')) {
            $query->where('overall_rating', '>=', $request->min_rating);
        }

        if ($request->filled('max_rating')) {
            $query->where('overall_rating', '<=', $request->max_rating);
        }

        $reviews = $query->latest()->paginate(20)->withQueryString();

        return view('admin.ratings.index', compact('reviews'));
    }

    public function show(RatingReview $review)
    {
        $review->load(['user', 'order', 'moderator']);

        return view('admin.ratings.show', compact('review'));
    }

    public function update(Request $request, RatingReview $review)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected,flagged',
            'moderation_notes' => 'nullable|string',
            'business_response' => 'nullable|string',
            'is_featured' => 'boolean',
            'is_public' => 'boolean',
        ]);

        $validated['moderated_at'] = now();
        $validated['moderated_by'] = auth()->id();
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_public'] = $request->boolean('is_public');
        if (filled($validated['business_response'] ?? null) && ! $review->business_responded_at) {
            $validated['business_responded_at'] = now();
        }

        $review->update($validated);

        return redirect()->route('admin.ratings.show', $review)
            ->with('success', 'Review updated successfully.');
    }
}
