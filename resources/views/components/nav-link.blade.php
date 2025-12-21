@props(['href', 'active' => false])

<a
    href="{{ $href }}"
    {{ $attributes->merge([
        'class' => $active
            ? 'text-[--color-pandan-green] font-semibold'
            : 'text-[--color-sky-blue] hover:text-[--color-sky-blue-light] font-medium'
    ]) }}
>
    {{ $slot }}
</a>
