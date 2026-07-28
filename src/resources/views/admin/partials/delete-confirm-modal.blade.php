<div class="modal fade" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" class="delete-confirm-form">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Are you sure you want to delete this {{ $label ?? 'record' }}? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash mr-1"></i> Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>
@push('js')
<script>
    $(function () {
        $('#{{ $modalId }}').on('show.bs.modal', function (event) {
            var action = $(event.relatedTarget).data('action');
            $(this).find('form.delete-confirm-form').attr('action', action);
        });
    });
</script>
@endpush
