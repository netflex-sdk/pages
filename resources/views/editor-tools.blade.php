@mode('edit')
{!! $editorTools !!}
{{ $slot }}
@endmode
<style>
button, a, input[type="submit"] { pointer-events: none!important; }
a.netflex-content-settings-btn { pointer-events: auto!important; }
</style>