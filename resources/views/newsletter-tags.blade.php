@if(sizeof($tags))
<div id="netflex-advanced-yay-widget-header" style="padding: 10px 20px; border-bottom: 1px #cdcdcd solid; padding-bottom: 1rem;">
  <span style="margin-right:10px;"> + </span>Innholdstyper
</div>
<div class="tag-list closed" style="overflow: scroll; display: flex; flex-direction: column; border-bottom: 1px #cdcdcd solid; ">
  <div style="padding: 10px 20px; font-size: 0.75rem;">
    @verbatim
    <p>Det er mulig å bruke variabler i epostene. Disse variablene vil erstattes med korrekte verdier for kunden som skal motta mailen.</p>
    <p>Noen av disse automasjonsmailene har tilgang på mer informasjon en bare kunde info, basert på bruksområdet. Dette kan være ting som ordre, billetter eller liknende.</p>
    <p>
      For å sette inn en variabel, trykk i et innholdsområde, slik at du kan redigere teksten der du ønsker å sette inn variabelen.
      Trykk så på navnet på variabelen i listen under denne meldingen, eller skriv inn navnet på ønsket variabel med tegn rundt slik som dette
      <div style="padding: 4px 2px; background: #cdcdcd; border-radius: 6px; box-shadow: 1 1 2px rgba(0,0,0,0.2); font-family: mono; display: inline-block;">{{ data.order.secret }}</div>
    </p>
    @endverbatim
  </div>
  @include("pages::newsletter-tag", ['tag' => $tags, 'prefix' => []])
</div>
<style>
  .tag-list {
    max-height:  50vh;
    padding: 1rem 0rem;

    transition: 100ms 100ms ease-in;
  }

  .tag-list.closed {
    max-height: 0vh;
    padding: 0;
  }

  .nf-automation-mail-tag-header,
  .nf-automation-mail-tag-option {
    padding: 0.5rem 2rem;
    background: transparent;
    border: 0;

    text-align: left;
  }

  .nf-automation-mail-tag-option {
    width: 100%;
  }
  .nf-automation-mail-tag-option > * {
    pointer-events: none;
  }

  .nf-automation-mail-tag-option:hover {
    cursor: pointer;
    background: #fefefe;
  }
</style>
<script>
  document.querySelector("#netflex-advanced-yay-widget-header").addEventListener('click', function(event) {
    event.target.parentElement.querySelector('.tag-list').classList.toggle('closed')
  })

  let currentEditor;

  CKEDITOR.on( 'instanceReady', function( event ) {
    event.editor.on( 'focus', function() {
      console.log( 'focused', this );

      currentEditor = this
    });
  });

  document.querySelectorAll('.nf-automation-mail-tag-option').forEach(element => {
    element.addEventListener('click', function(event) {
      event.preventDefault()
      console.log(event.target)
      console.log(currentEditor?.getSelection().getRanges()[0])
      @verbatim
      currentEditor?.insertText(`{{ ${event.target.getAttribute('data-option')} }}`)
      @endverbatim
    })
  })
</script>
@endif
