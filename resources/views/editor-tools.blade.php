@mode('edit')
{!! $editorTools !!}
{{ $slot }}
@endmode
<style>
button, a, input[type="submit"] { pointer-events: none!important; }
.find-image, .find-image *, .cke, .cke *, .netflex-advanced-content-widget, .netflex-advanced-content-widget *, .netflex-content-settings-btn, .netflex-content-settings-btn * { pointer-events: auto!important; }
</style>