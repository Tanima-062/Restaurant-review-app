----------------------------------------------
@if ($reservation->total !== 0)
@if (!empty($cancelFees))
■店舗キャンセルポリシー
@foreach ($cancelFees as $cancelFee)
{{$cancelFee}}
@endforeach
@endif
@endif

▼ご予約内容に変更・キャンセルがあった場合
下記のURLにアクセスし、予約番号とご予約時の電話番号を入力してください。
https://skyticket.jp/gourmet/mypage/login/

※ご予約内容確認画面からの変更・キャンセル操作期間は{{$cancelLimit}}です。

----------------------------------------------
