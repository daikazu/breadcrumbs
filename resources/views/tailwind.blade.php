@if($breadcrumbs->isNotEmpty())
<nav aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2 text-sm text-gray-500">
        @foreach($breadcrumbs as $crumb)
            <li class="flex items-center">
                @if(!$loop->first)
                    <svg class="mx-2 h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                    </svg>
                @endif
                @if($crumb->active || $crumb->url === '')
                    <span @if($crumb->active) aria-current="page" class="font-medium text-gray-900" @else class="text-gray-400" @endif>
                        {{ $crumb->label }}
                    </span>
                @else
                    <a href="{{ $crumb->url }}" class="hover:text-gray-700">{{ $crumb->label }}</a>
                @endif
            </li>
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
