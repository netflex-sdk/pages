@mode('edit')
{!! $editorTools !!}
{{ $slot }}
@endmode
<style>
button, a, input[type="submit"] { pointer-events: none!important; }
.find-image, .find-image *, .cke, .cke *, .cke_reset_all, .cke_reset_all *, .netflex-advanced-content-area, .netflex-advanced-content-area *, .netflex-content-settings-btn, .netflex-content-settings-btn * { pointer-events: auto!important; }
</style>