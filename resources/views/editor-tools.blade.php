@mode('edit')
{!! $editorTools !!}
{{ $slot }}
@endmode
<style>
button, a, input[type="submit"] { pointer-events: none!important; }
.find-image, .cke_button, .cke_button__link, .netflex-advanced-content-area, .netflex-content-settings-btn { pointer-events: auto!important; }
</style>