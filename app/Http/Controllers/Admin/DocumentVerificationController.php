<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentVerification;
use App\Traits\SearchEscaping;
use Illuminate\Http\Request;

class DocumentVerificationController extends Controller
{
    use SearchEscaping;
    public function index(Request $request)
    {
        $query = DocumentVerification::with(['user', 'verifier']);

        if ($request->filled('search')) {
            $search = $this->escapeLikePattern($request->search);
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $verifications = $query->latest('submitted_at')->paginate(20)->withQueryString();

        return view('admin.document-verifications.index', compact('verifications'));
    }

    public function show(DocumentVerification $documentVerification)
    {
        $documentVerification->load(['user', 'verifier']);

        return view('admin.document-verifications.show', compact('documentVerification'));
    }

    public function update(Request $request, DocumentVerification $documentVerification)
    {
        $validated = $request->validate([
            'status' => 'required|in:under_review,approved,rejected',
            'admin_notes' => 'nullable|string',
            'rejection_reason' => 'required_if:status,rejected|nullable|string',
        ]);

        $updateData = [
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? null,
            'rejection_reason' => $validated['rejection_reason'] ?? null,
            'verified_by' => auth()->id(),
            'reviewed_at' => now(),
        ];

        $documentVerification->update($updateData);

        return redirect()->route('admin.document-verifications.show', $documentVerification)
            ->with('success', 'Document verification updated successfully.');
    }
}
