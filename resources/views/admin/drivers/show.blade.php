@extends('admin.layouts.app')
@section('title', 'Driver Details - LesGo Admin')
@section('header', 'Driver Details')

@section('actions')
<a href="{{ route('admin.drivers.edit', $driver) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm"><i class="fas fa-edit mr-1"></i> Edit</a>
<button type="button" class="bg-{{ $driver->status === 'active' ? 'red' : 'green' }}-500 text-white px-4 py-2 rounded-lg text-sm"
    x-data
    @click="$dispatch('confirm-modal', {
        title: '{{ $driver->status === 'active' ? 'Deactivate' : 'Activate' }} Driver',
        message: 'Are you sure you want to {{ $driver->status === 'active' ? 'deactivate' : 'activate' }} this driver?',
        confirmText: '{{ $driver->status === 'active' ? 'Deactivate' : 'Activate' }}',
        confirmClass: '{{ $driver->status === 'active' ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700' }}',
        onConfirm: () => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('admin.drivers.toggle', $driver) }}';
            const i1=document.createElement('input');i1.type='hidden';i1.name='_token';i1.value='{{ csrf_token() }}';form.appendChild(i1);
            document.body.appendChild(form);
            form.submit();
        }
    })">
    <i class="fas fa-{{ $driver->status === 'active' ? 'ban' : 'check' }} mr-1"></i> {{ $driver->status === 'active' ? 'Deactivate' : 'Activate' }}
</button>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="text-center mb-4">
            <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center font-bold text-2xl mx-auto mb-4">
                {{ substr($driver->user?->name ?? '?', 0, 1) }}
            </div>
            <h3 class="text-xl font-bold text-gray-800">{{ $driver->user?->name ?? 'N/A' }}</h3>
            <p class="text-gray-500 text-sm">{{ $driver->user?->email ?? '' }}</p>
            <x-status-badge status="{{ $driver->status }}" />
        </div>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Rating</span><span>{{ $driver->rating }} <i class="fas fa-star text-yellow-400 text-xs"></i></span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Total Trips</span><span>{{ $driver->total_trips }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">License #</span><span>{{ $driver->license_number ?? '-' }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">License Expiry</span><span>{{ $driver->license_expiry_date ? $driver->license_expiry_date->format('M d, Y') : '-' }}</span></div>
            <div class="flex justify-between border-b pb-2"><span class="text-gray-500">Package Tier</span><span>{{ $driver->package_tier ?? '-' }}</span></div>
        </div>
    </div>

    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Vehicle Information</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div><p class="text-gray-500">Type</p><p class="font-medium">{{ $driver->vehicle_type ?? '-' }}</p></div>
                <div><p class="text-gray-500">Plate #</p><p class="font-medium">{{ $driver->plate_number ?? '-' }}</p></div>
                <div><p class="text-gray-500">Last Location</p><p class="font-medium text-xs">{{ $driver->last_latitude ? $driver->last_latitude . ', ' . $driver->last_longitude : '-' }}</p></div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Documents & Requirements</h3>
            @php
                $docs = $driver->documents ?? [];
                $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
                $idDocUrl = null;
                if ($driver->id_document_path) {
                    $p = $driver->id_document_path;
                    $idDocUrl = \Illuminate\Support\Str::startsWith($p, ['http://','https://']) ? $p : \Illuminate\Support\Facades\Storage::disk($disk)->url($p);
                }
            @endphp
            @if($idDocUrl)
                <div class="mb-4">
                    <p class="text-sm text-gray-500 mb-2">ID Document</p>
                    <a href="{{ $idDocUrl }}" target="_blank" class="block">
                        @if(\Illuminate\Support\Str::endsWith(strtolower($idDocUrl), '.pdf'))
                            <span class="inline-flex items-center gap-2 text-sm text-blue-600 hover:underline"><i class="fas fa-file-pdf"></i> View PDF</span>
                            <iframe src="{{ $idDocUrl }}" class="w-full h-64 rounded-lg border mt-2"></iframe>
                        @else
                            <img src="{{ $idDocUrl }}" alt="ID Document" class="max-h-64 rounded-lg border" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                            <p style="display:none" class="text-xs text-red-500 mt-2">Failed to load image. <a href="{{ $idDocUrl }}" target="_blank" class="underline">Open directly</a> — check storage disk / S3 / symlink.</p>
                        @endif
                    </a>
                    <p class="text-xs text-gray-400 mt-1 break-all">{{ $driver->id_document_path }} → {{ $idDocUrl }}</p>
                </div>
            @endif
            @if(!empty($docs))
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                    @foreach($docs as $key => $url)
                        @if(is_string($url))
                            @php $docUrl = \Illuminate\Support\Str::startsWith($url, ['http://','https://']) ? $url : \Illuminate\Support\Facades\Storage::disk($disk)->url($url); @endphp
                            <div class="border rounded-lg p-3">
                                <p class="text-xs font-medium text-gray-500 uppercase">{{ str_replace('_',' ', $key) }}</p>
                                <a href="{{ $docUrl }}" target="_blank" class="block mt-2">
                                    @if(\Illuminate\Support\Str::endsWith(strtolower($docUrl), '.pdf'))
                                        <span class="text-xs text-blue-600 hover:underline"><i class="fas fa-file-pdf"></i> View PDF</span>
                                    @else
                                        <img src="{{ $docUrl }}" alt="{{ $key }}" class="h-32 w-full object-cover rounded" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                        <p style="display:none" class="text-xs text-red-500">Failed to load</p>
                                    @endif
                                </a>
                                <p class="text-xs text-blue-600 truncate mt-1 break-all" title="{{ $docUrl }}">{{ $docUrl }}</p>
                            </div>
                        @endif
                    @endforeach
                </div>
            @elseif(!$idDocUrl)
                <p class="text-sm text-gray-400 mb-6">No driver documents uploaded yet (documents JSON empty).</p>
            @endif

            <h4 class="font-medium text-gray-700 mb-3">Document Verifications <span class="text-xs text-gray-400">({{ $driver->user?->documentVerifications?->count() ?? 0 }})</span></h4>
            @forelse($driver->user?->documentVerifications ?? [] as $doc)
                <div class="flex items-center justify-between gap-3 border rounded-lg px-4 py-3 mb-2">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800">{{ ucfirst(str_replace('_',' ', $doc->document_type)) }} <span class="text-xs text-gray-400">#{{ $doc->document_number ?? '-' }}</span></p>
                        <p class="text-xs text-gray-500">Submitted {{ $doc->submitted_at?->diffForHumans() }} @if($doc->expires_at) · Expires {{ $doc->expires_at->format('M d, Y') }} @endif</p>
                        @if($doc->rejection_reason)<p class="text-xs text-red-600">Reason: {{ $doc->rejection_reason }}</p>@endif
                    </div>
                    <div class="flex items-center gap-2">
                        <x-status-badge :status="$doc->status" />
                        <a href="{{ route('admin.document-verifications.show', $doc) }}" class="text-blue-600 text-xs">Review</a>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-400">No verification records for this rider.</p>
            @endforelse
            <div class="mt-4"><a href="{{ route('admin.document-verifications.index', ['search' => $driver->user?->email]) }}" class="text-sm text-blue-600 hover:underline">View all verifications →</a></div>

            <div class="mt-8 border-t pt-6 space-y-6">
                <h4 class="font-medium text-gray-700">Add / Edit Documents (Admin)</h4>
                <form method="POST" action="{{ route('admin.drivers.documents.update', $driver) }}" enctype="multipart/form-data" class="space-y-4 bg-gray-50 p-4 rounded-lg">
                    @csrf @method('PUT')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ID Document (id_document_path)</label>
                        <input type="file" name="id_document" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm border rounded-lg px-3 py-2">
                        @if($driver->id_document_path)<label class="inline-flex items-center gap-2 mt-2 text-xs"><input type="checkbox" name="remove_id_document" value="1"> Remove current</label>@endif
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><label class="block text-xs text-gray-500 mb-1">documents[license_front]</label><input type="file" name="documents[license_front]" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm border rounded px-2 py-1"></div>
                        <div><label class="block text-xs text-gray-500 mb-1">documents[or_cr]</label><input type="file" name="documents[or_cr]" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm border rounded px-2 py-1"></div>
                        <div><label class="block text-xs text-gray-500 mb-1">documents[nbi_clearance]</label><input type="file" name="documents[nbi_clearance]" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm border rounded px-2 py-1"></div>
                        <div><label class="block text-xs text-gray-500 mb-1">documents[vehicle_photo]</label><input type="file" name="documents[vehicle_photo]" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm border rounded px-2 py-1"></div>
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">Save Documents</button>
                </form>

                <form method="POST" action="{{ route('admin.drivers.documents.store', $driver) }}" enctype="multipart/form-data" class="space-y-3 bg-white border p-4 rounded-lg">
                    @csrf
                    <p class="text-sm font-medium">Add Verification Record</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div><label class="block text-xs text-gray-500 mb-1">Document Type</label><select name="document_type" required class="w-full border rounded px-3 py-2 text-sm"><option value="driver_license">Driver License</option><option value="vehicle_registration">Vehicle Registration</option><option value="or_cr">OR/CR</option><option value="nbi_clearance">NBI Clearance</option><option value="brgy_clearance">Brgy Clearance</option><option value="vehicle_photo">Vehicle Photo</option><option value="valid_id">Valid ID</option><option value="other">Other</option></select></div>
                        <div><label class="block text-xs text-gray-500 mb-1">Document Number</label><input name="document_number" placeholder="e.g. DL12345" class="w-full border rounded px-3 py-2 text-sm"></div>
                        <div><label class="block text-xs text-gray-500 mb-1">File (jpg/png/pdf, 5MB)</label><input type="file" name="document_file" required accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm border rounded px-3 py-2"></div>
                        <div><label class="block text-xs text-gray-500 mb-1">Expires At</label><input type="date" name="expires_at" class="w-full border rounded px-3 py-2 text-sm"></div>
                    </div>
                    <div><label class="block text-xs text-gray-500 mb-1">Description</label><textarea name="description" rows="2" class="w-full border rounded px-3 py-2 text-sm" placeholder="Notes..."></textarea></div>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm">Add Verification</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
