{{-- Credit: Lucide (https://lucide.dev) --}}

@props([
    'variant' => 'outline',
])

@php
    if ($variant === 'solid') {
        throw new \Exception('The "solid" variant is not supported.');
    }

    $classes = Flux::classes('shrink-0')
        ->add(match($variant) {
            'outline' => '[:where(&)]:size-6',
            'solid' => '[:where(&)]:size-6',
            'mini' => '[:where(&)]:size-5',
            'micro' => '[:where(&)]:size-4',
        });

    $strokeWidth = match ($variant) {
        'outline' => 2,
        'mini' => 2.25,
        'micro' => 2.5,
    };
@endphp

<svg
    {{ $attributes->class($classes) }}
    data-flux-icon
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 150 150"
    fill="none"
    stroke="currentColor"
    stroke-width="{{ $strokeWidth }}"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    data-slot="icon"
>
    <path d="M77.0469 55.6795L61.3237 50.1562L58.4039 57.45L74.1271 62.9733L77.0469 55.6795Z"
          fill="currentColor"></path>
    <path d="M85.624 48.188L90.5814 63.4936L83.0328 65.9585L78.0754 50.6529L85.624 48.188Z" fill="currentColor"></path>
    <path d="M93.2339 79.3661L102.061 65.9315L95.4349 61.5425L86.608 74.9771L93.2339 79.3661Z"
          fill="currentColor"></path>
    <path d="M98.4916 89.8978L82.5273 91.3448L81.8136 83.407L97.7779 81.9599L98.4916 89.8978Z"
          fill="currentColor"></path>
    <path d="M66.5245 90.4056L77.6049 102.036L83.3407 96.5263L72.2604 84.8962L66.5245 90.4056Z"
          fill="currentColor"></path>
    <path d="M55.1287 93.217L57.2761 77.2674L65.1423 78.3351L62.9949 94.2847L55.1287 93.217Z"
          fill="currentColor"></path>
    <path d="M61.7458 61.8034L47.9877 70.062L52.0608 76.9029L65.8189 68.6443L61.7458 61.8034Z"
          fill="currentColor"></path>
    <path fill-rule="evenodd" clip-rule="evenodd"
          d="M25 33.5C22.216 33.5 19.5852 34.4455 17.6403 36.3903C15.6955 38.3352 14.75 40.966 14.75 43.75V106.25C14.75 108.968 15.8299 111.576 17.7522 113.498C19.6744 115.42 22.2815 116.5 25 116.5H125C127.784 116.5 130.415 115.555 132.36 113.61C134.305 111.665 135.25 109.034 135.25 106.25V43.75C135.25 40.966 134.305 38.3352 132.36 36.3903C130.415 34.4455 127.784 33.5 125 33.5H25ZM22.75 43.75C22.75 42.784 23.0545 42.2898 23.2972 42.0472C23.5398 41.8045 24.034 41.5 25 41.5H125C125.966 41.5 126.46 41.8045 126.703 42.0472C126.945 42.2898 127.25 42.784 127.25 43.75V106.25C127.25 107.216 126.945 107.71 126.703 107.953C126.46 108.195 125.966 108.5 125 108.5H25C24.4033 108.5 23.831 108.263 23.409 107.841C22.9871 107.419 22.75 106.847 22.75 106.25V43.75Z"
          fill="currentColor"></path>
</svg>
