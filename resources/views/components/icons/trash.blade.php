@props(['class' => 'w-4 h-4'])

<svg {{ $attributes->merge(['class' => $class]) }} fill="currentColor" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
    <title>trash-can</title>
    <rect x="12" y="12" width="2" height="12"/>
    <rect x="18" y="12" width="2" height="12"/>
    <path d="M4,6V8H6V28a2,2,0,0,0,2,2H24a2,2,0,0,0,2-2V8h2V6ZM8,28V8H24V28Z"/>
    <rect x="12" y="2" width="8" height="2"/>
</svg> 