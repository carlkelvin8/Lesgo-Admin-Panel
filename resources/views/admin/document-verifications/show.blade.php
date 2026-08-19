@extends('admin.layouts.app')
@section('title', 'Verify Document - LesGo Admin')
@section('header', 'Document Verification Details')

@section('content')
@php
    $statusColors = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'under_review' => 'bg-blue-100 text-blue-800',
        'approved' => 'bg-green-100 text-green-800',
        'rejected' => 'bg-red-100 text-red-800',
        'expired' => 'bg-gray-100 text-gray-800',
    ];
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- User Info -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-user mr-2 text-blue-600"></i>User Information</h3>
        <div class="text-center mb-4">
            <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-bold text-xl mx-auto mb-3">
                {{ $documentVerification->user ? substr($documentVerification->user->name, 0, 1) : '?' }}
            </div>
            <h4 class="font-bold text-gray-800">{{ $documentVerification->user->name ?? 'Deleted User' }}</h4>
            <p class="text-sm text-gray-500">{{ $documentVerification->user->email ?? '-' }}</p>
        </div>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between border-b pb-2">
                <span class="text-gray-500">Phone</span>
                <span class="text-gray-800">{{ $documentVerification->user->phone_number ?? '-' }}</span>
            </div>
            <div class="flex justify-between border-b pb-2">
                <span class="text-gray-500">Role</span>
                <span class="text-gray-800">{{ ucfirst($documentVerification->user->role ?? '-') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Joined</span>
                <span class="text-gray-800">{{ $documentVerification->user?->created_at?->format('M d, Y') ?? '-' }}</span>
            </div>
        </div>
    </div>

    <!-- Document Info & Actions -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Document Details -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-800"><i class="fas fa-id-card mr-2 text-purple-600"></i>Document Information</h3>
                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusColors[$documentVerification->status] ?? 'bg-gray-100 text-gray-700' }}">{{ ucfirst(str_replace('_', ' ', $documentVerification->status)) }}</span>
            </div>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between border-b pb-2">
                    <span class="text-gray-500">Document Type</span>
                    <span class="text-gray-800 font-medium">{{ ucfirst(str_replace('_', ' ', $documentVerification->document_type)) }}</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="text-gray-500">Document Number</span>
                    <span class="text-gray-800 font-mono">{{ $documentVerification->document_number ?? '-' }}</span>
                </div>
                @if($documentVerification->description)
                <div class="border-b pb-2">
                    <span class="text-gray-500">Description</span>
                    <p class="text-gray-800 mt-1">{{ $documentVerification->description }}</p>
                </div>
                @endif
                <div class="flex justify-between border-b pb-2">
                    <span class="text-gray-500">Submitted</span>
                    <span class="text-gray-800">{{ $documentVerification->submitted_at?->format('M d, Y H:i') ?? '-' }}</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="text-gray-500">Verification Attempts</span>
                    <span class="text-gray-800">{{ $documentVerification->verification_attempts ?? 0 }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Last Attempt</span>
                    <span class="text-gray-800">{{ $documentVerification->last_attempt_at?->diffForHumans() ?? '-' }}</span>
                </div>
            </div>
        </div>

        <!-- Document URLs -->
        @if(!empty($documentVerification->document_urls))
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 mb-3"><i class="fas fa-link mr-2 text-green-600"></i>Document Files</h3>
            <div class="space-y-2">
                @foreach($documentVerification->document_urls as $index => $url)
                    <a href="{{ $url }}" target="_blank" class="flex items-center gap-2 text-sm text-blue-600 hover:text-blue-800 hover:underline">
                        <i class="fas fa-file-alt"></i>
                        Document {{ $index + 1 }}
                        <i class="fas fa-external-link-alt text-xs"></i>
                    </a>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Admin Action Form -->
        @if(in_array($documentVerification->status, ['pending', 'under_review']))
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 mb-4"><i class="fas fa-gavel mr-2 text-orange-600"></i>Review Document</h3>
            <form method="POST" action="{{ route('admin.document-verifications.update', $documentVerification) }}">
                @csrf
                @method('PATCH')
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Status</label>
                        <select name="status" id="reviewStatus" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500 outline-none">
                            <option value="under_review" {{ $documentVerification->status === 'under_review' ? 'selected' : '' }}>Under Review</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Admin Notes</label>
                        <textarea name="admin_notes" rows="3" placeholder="Internal notes about this review..."
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500 outline-none">{{ $documentVerification->admin_notes }}</textarea>
                    </div>
                    <div id="rejectionReasonDiv" style="display: none;">
                        <label class="block text-xs text-gray-500 mb-1">Rejection Reason</label>
                        <textarea name="rejection_reason" rows="3" placeholder="Explain why this document is being rejected..."
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500 outline-none">{{ $documentVerification->rejection_reason }}</textarea>
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-medium">Submit Review</button>
                </div>
            </form>
        </div>
        @endif

        <!-- Audit Trail -->
        @if($documentVerification->reviewed_at)
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 mb-3"><i class="fas fa-history mr-2 text-gray-600"></i>Review History</h3>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between border-b pb-2">
                    <span class="text-gray-500">Reviewed By</span>
                    <span class="text-gray-800">{{ $documentVerification->verifier->name ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Reviewed At</span>
                    <span class="text-gray-800">{{ $documentVerification->reviewed_at->format('M d, Y H:i') }}</span>
                </div>
                @if($documentVerification->expires_at)
                <div class="flex justify-between border-t pt-2 mt-2">
                    <span class="text-gray-500">Expires At</span>
                    <span class="text-gray-800">{{ $documentVerification->expires_at->format('M d, Y H:i') }}</span>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

@section('scripts')
<script>
    const statusSelect = document.getElementById('reviewStatus');
    const rejectionDiv = document.getElementById('rejectionReasonDiv');
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            rejectionDiv.style.display = this.value === 'rejected' ? 'block' : 'none';
        });
        rejectionDiv.style.display = statusSelect.value === 'rejected' ? 'block' : 'none';
    }
</script>
@endsection
@endsection
