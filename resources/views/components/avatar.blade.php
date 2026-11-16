@props(['user' => null, 'size' => ''])

@php
    $person = $user ?? auth()->user();
    $color = $person?->avatar_color ?: 'violet';
    $initials = $person?->initials() ?? '?';
@endphp

<span {{ $attributes->merge(['class' => 'avatar '.$size.' '.$color]) }} title="{{ $person?->name }}">{{ $initials }}</span>
