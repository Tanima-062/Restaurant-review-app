■ご予約の店舗
{{$store->name}}
〒{{$store->postal_code}}
{{$store->address_1}}{{$store->address_2}}{{$store->address_3}}
{{$store->tel}}
▼店舗詳細は下記URLよりご確認ください。
{{ $baseUrl.$restaurantDetailUrl.$store->id.'/' }}