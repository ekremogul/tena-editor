@php
    $level = $data['level'] ?? 3;
    $tag = "h{$level}";
@endphp
<{{ $tag }}>{{ $data['text'] ?? '' }}</{{ $tag }}>
