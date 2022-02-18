{!! $editorTools !!}
{{ $slot }}
<style>
    button, a, input[type="submit"] { pointer-events: none!important; }
    .find-image, .find-image *, .cke, .cke *, .cke_reset_all, .cke_reset_all *, .netflex-advanced-content-area, .netflex-advanced-content-area *, .netflex-content-settings-btn, .netflex-content-settings-btn * { pointer-events: auto!important; }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        alert('hei')
        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                e.preventBubbling();
                return false;
            });
        });
        document.querySelectorAll('button').forEach(function (form) {
            form.addEventListener('click', function (e) {
                e.preventDefault();
                e.preventBubbling();
                return false;
            });
        });
    })
</script>