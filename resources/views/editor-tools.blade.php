@mode('edit')
{!! $editorTools !!}
{{ $slot }}
@endmode
<style>
// Disable links and form submissions in edit/preview mode
button, a, input[type="submit"] { pointer-events: none!important; }
a.netflex-content-settings-btn { pointer-events: auto!important; }
</style>