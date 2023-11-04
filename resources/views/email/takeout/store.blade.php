■ご注文の店舗
{{$store->name}}
〒{{$store->postal_code}}
{{$store->address_1}}{{$store->address_2}}{{$store->address_3}}
{{$store->tel}}
@php $openingHoursText = '';@endphp
@foreach ($openingHours as $hours)
@if (!$loop->first)
    @php $openingHoursText = $openingHoursText . '/'; @endphp
@endif
@php $openingHoursText = $openingHoursText . $hours->start_at.'~'.$hours->end_at @endphp
@endforeach

@php echo $openingHoursText @endphp
