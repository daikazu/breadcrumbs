@if($trail->isNotEmpty())
<script type="application/ld+json">
{!! json_encode($trail->toSchema(), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
</script>
@endif
