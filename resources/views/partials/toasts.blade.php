{{-- Toasts auto-disparaissants, partagés par toutes les pages (y compris login). --}}
<div class="toast-container position-fixed end-0 app-toast-container p-3" style="z-index:1080;">
    @if(session('success'))
        <div class="toast align-items-center border-0 app-toast app-toast-success" role="alert" data-bs-delay="3500">
            <div class="d-flex">
                <div class="toast-body">{{ session('success') }}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    @endif
    @if(session('error') || $errors->any())
        <div class="toast align-items-center border-0 app-toast app-toast-error" role="alert" data-bs-delay="4500">
            <div class="d-flex">
                <div class="toast-body">{{ session('error') ?? $errors->first() }}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    @endif
    @if(session('warning') || session('info'))
        <div class="toast align-items-center border-0 app-toast app-toast-warning" role="alert" data-bs-delay="4000">
            <div class="d-flex">
                <div class="toast-body">{{ session('warning') ?? session('info') }}</div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    @endif
</div>

<script>
    document.querySelectorAll('.app-toast').forEach(el => {
        new bootstrap.Toast(el).show();
    });
</script>
