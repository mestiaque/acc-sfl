{{-- Makes every <select> on the page searchable via Select2 (already loaded globally by
     the host admin theme). Scoped per-element so selects inside a Bootstrap modal get
     dropdownParent set to that modal — otherwise Select2's search box loses keyboard
     input inside a modal, since Select2 attaches its dropdown to <body> by default and
     the modal's focus-trap intercepts the keystrokes. Call acSelect2Init() again after
     injecting new <select> elements at runtime (e.g. a dynamically added table row). --}}
<script>
    function acSelect2Init(scope) {
        var $scope = scope ? $(scope) : $(document);
        $scope.find('select').addBack('select').each(function () {
            var $el = $(this);
            if ($el.hasClass('select2-hidden-accessible')) {
                return;
            }
            var $modal = $el.closest('.modal');
            $el.select2({
                width: '100%',
                dropdownParent: $modal.length ? $modal : $(document.body),
            });
        });
    }

    $(function () {
        acSelect2Init();
    });
</script>
