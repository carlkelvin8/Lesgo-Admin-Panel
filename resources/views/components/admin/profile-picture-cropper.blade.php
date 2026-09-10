@props(['inputId' => 'profile_picture_input', 'hiddenInputId' => 'cropped_profile_picture', 'previewId' => 'cropper-preview-img', 'existingUrl' => null])

<div class="mb-2 flex items-center gap-4">
    <div class="relative">
        <img id="{{ $previewId }}" src="{{ $existingUrl ?? '' }}" alt="Preview" class="{{ $existingUrl ? '' : 'hidden' }} w-24 h-24 rounded-full object-cover border-2 border-gray-200 shadow-sm">
        <div id="{{ $previewId }}-placeholder" class="{{ $existingUrl ? 'hidden' : '' }} w-24 h-24 rounded-full bg-gray-100 border-2 border-dashed border-gray-300 flex items-center justify-center text-gray-400">
            <i class="fas fa-user text-xl"></i>
        </div>
    </div>
    <div class="flex-1">
        <label class="block text-xs font-medium text-gray-600 mb-1">Profile Picture</label>
        <input type="file" id="{{ $inputId }}" name="profile_picture" accept=".jpg,.jpeg,.png,.webp" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 file:mr-3 file:py-1 file:px-3 file:rounded-full file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
        <p class="text-xs text-gray-500 mt-1">JPG/PNG/WEBP, max 2MB — will be cropped to 1:1 circle</p>
        <input type="hidden" name="cropped_profile_picture" id="{{ $hiddenInputId }}">
        <div id="{{ $inputId }}-actions" class="hidden mt-2 flex gap-2">
            <button type="button" id="{{ $inputId }}-clear" class="text-xs text-red-600 hover:text-red-700"><i class="fas fa-times mr-1"></i> Clear</button>
            <span id="{{ $inputId }}-status" class="text-xs text-green-600 hidden"><i class="fas fa-check mr-1"></i> Cropped ready</span>
        </div>
    </div>
</div>

<!-- Cropper Modal -->
<div id="{{ $inputId }}-modal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" id="{{ $inputId }}-backdrop"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col overflow-hidden">
        <div class="px-5 py-4 border-b flex items-center justify-between">
            <h3 class="font-semibold text-gray-800"><i class="fas fa-crop mr-2 text-blue-600"></i> Crop Profile Picture</h3>
            <button type="button" id="{{ $inputId }}-close" class="w-8 h-8 rounded-full hover:bg-gray-100 flex items-center justify-center text-gray-500"><i class="fas fa-times"></i></button>
        </div>
        <div class="flex-1 p-4 bg-gray-900 overflow-hidden flex items-center justify-center" style="min-height:300px">
            <img id="{{ $inputId }}-cropper-img" class="max-w-full max-h-[60vh] hidden">
        </div>
        <div class="px-5 py-3 bg-gray-50 border-t flex items-center justify-between">
            <div class="flex items-center gap-2">
                <button type="button" id="{{ $inputId }}-rotate-left" class="w-8 h-8 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 flex items-center justify-center text-gray-600" title="Rotate left"><i class="fas fa-undo text-xs"></i></button>
                <button type="button" id="{{ $inputId }}-rotate-right" class="w-8 h-8 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 flex items-center justify-center text-gray-600" title="Rotate right"><i class="fas fa-redo text-xs"></i></button>
                <button type="button" id="{{ $inputId }}-zoom-in" class="w-8 h-8 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 flex items-center justify-center text-gray-600" title="Zoom in"><i class="fas fa-search-plus text-xs"></i></button>
                <button type="button" id="{{ $inputId }}-zoom-out" class="w-8 h-8 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 flex items-center justify-center text-gray-600" title="Zoom out"><i class="fas fa-search-minus text-xs"></i></button>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" id="{{ $inputId }}-cancel" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm">Cancel</button>
                <button type="button" id="{{ $inputId }}-confirm" class="px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium"><i class="fas fa-check mr-1"></i> Crop & Use</button>
            </div>
        </div>
    </div>
</div>

@once
@push('head')
<style>.cropper-point{width:8px;height:8px}</style>
@endpush
@endonce

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (typeof Cropper === 'undefined') { console.error('Cropper.js not loaded'); return; }
    const input = document.getElementById('{{ $inputId }}');
    const hidden = document.getElementById('{{ $hiddenInputId }}');
    const preview = document.getElementById('{{ $previewId }}');
    const placeholder = document.getElementById('{{ $previewId }}-placeholder');
    const actions = document.getElementById('{{ $inputId }}-actions');
    const status = document.getElementById('{{ $inputId }}-status');
    const modal = document.getElementById('{{ $inputId }}-modal');
    const backdrop = document.getElementById('{{ $inputId }}-backdrop');
    const closeBtn = document.getElementById('{{ $inputId }}-close');
    const cancelBtn = document.getElementById('{{ $inputId }}-cancel');
    const confirmBtn = document.getElementById('{{ $inputId }}-confirm');
    const clearBtn = document.getElementById('{{ $inputId }}-clear');
    const cropperImg = document.getElementById('{{ $inputId }}-cropper-img');
    let cropper = null;
    let originalFileName = '';

    const openModal = () => { modal.classList.remove('hidden'); document.body.style.overflow='hidden'; };
    const closeModal = () => {
        modal.classList.add('hidden'); document.body.style.overflow='';
        if (cropper) { cropper.destroy(); cropper=null; }
        // reset file input if cancelled without confirm and no hidden data
        if (!hidden.value) input.value='';
    };

    const showPreview = (dataUrl) => {
        preview.src = dataUrl;
        preview.classList.remove('hidden');
        placeholder?.classList.add('hidden');
        actions?.classList.remove('hidden');
        status?.classList.remove('hidden');
    };

    input?.addEventListener('change', (e) => {
        const file = e.target.files?.[0];
        if (!file) return;
        if (!file.type.match(/^image\//)) { alert('Please select an image file'); input.value=''; return; }
        if (file.size > 5*1024*1024) { alert('Image too large (max 5MB)'); input.value=''; return; }
        originalFileName = file.name;
        // Show selected feedback immediately (input's native "No file chosen" is confusing)
        actions?.classList.remove('hidden');
        status?.classList.remove('hidden');
        status.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Selected: ' + originalFileName + ' — opening cropper...';
        const url = URL.createObjectURL(file);
        cropperImg.src = url;
        cropperImg.classList.remove('hidden');
        openModal();
        // init cropper after image loads
        cropperImg.onload = () => {
            if (cropper) cropper.destroy();
            cropper = new Cropper(cropperImg, {
                aspectRatio: 1,
                viewMode: 1,
                autoCropArea: 1,
                movable: true,
                zoomable: true,
                rotatable: true,
                scalable: false,
                background: false,
                guides: true,
                center: true,
                highlight: true,
                cropBoxMovable: true,
                cropBoxResizable: true,
                dragMode: 'move',
            });
        };
    });

    confirmBtn?.addEventListener('click', () => {
        if (!cropper) return;
        const canvas = cropper.getCroppedCanvas({ width: 400, height: 400, imageSmoothingQuality: 'high', fillColor: '#fff' });
        if (!canvas) return;
        canvas.toBlob((blob) => {
            if (!blob) return;
            const reader = new FileReader();
            reader.onload = (ev) => {
                const dataUrl = ev.target.result;
                hidden.value = dataUrl; // base64 for backend
                showPreview(dataUrl);
                status.innerHTML = '<i class="fas fa-check mr-1"></i> Cropped ready: ' + (originalFileName || 'cropped.jpg') + ' (' + (blob.size/1024).toFixed(1) + ' KB)';
                // Replace file input with cropped file via DataTransfer for fallback (helps if JS disabled)
                try {
                    const dt = new DataTransfer();
                    const file = new File([blob], originalFileName || 'cropped.jpg', { type: 'image/jpeg' });
                    dt.items.add(file);
                    input.files = dt.files;
                } catch(e) { console.warn('DataTransfer not supported, using base64 only', e); }
            };
            reader.readAsDataURL(blob);
            closeModal();
        }, 'image/jpeg', 0.92);
    });

    clearBtn?.addEventListener('click', () => {
        input.value=''; hidden.value=''; preview.classList.add('hidden'); placeholder?.classList.remove('hidden');
        actions?.classList.add('hidden'); status?.classList.add('hidden');
    });
    closeBtn?.addEventListener('click', closeModal);
    cancelBtn?.addEventListener('click', closeModal);
    backdrop?.addEventListener('click', closeModal);
    document.getElementById('{{ $inputId }}-rotate-left')?.addEventListener('click', () => cropper?.rotate(-90));
    document.getElementById('{{ $inputId }}-rotate-right')?.addEventListener('click', () => cropper?.rotate(90));
    document.getElementById('{{ $inputId }}-zoom-in')?.addEventListener('click', () => cropper?.zoom(0.1));
    document.getElementById('{{ $inputId }}-zoom-out')?.addEventListener('click', () => cropper?.zoom(-0.1));
});
</script>
