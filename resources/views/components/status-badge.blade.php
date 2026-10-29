@props(['status'])

<span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-medium {{ $status->badgeClasses() }}">
    {{ $status->label() }}
</span>