@if($breadcrumbs->isNotEmpty())
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        @foreach($breadcrumbs as $crumb)
            @if($crumb->active)
                <li class="breadcrumb-item active" aria-current="page">{{ $crumb->label }}</li>
            @elseif($crumb->url !== '')
                <li class="breadcrumb-item"><a href="{{ $crumb->url }}">{{ $crumb->label }}</a></li>
            @else
                <li class="breadcrumb-item">{{ $crumb->label }}</li>
            @endif
        @endforeach
    </ol>
</nav>
@once
@if(config('breadcrumbs.livewire', false))
<script>
    document.addEventListener('livewire:navigate', () => {
        Livewire.dispatch('breadcrumbs:refresh');
    });
</script>
@endif
@endonce
@endif
