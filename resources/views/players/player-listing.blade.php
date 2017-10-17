@extends('template.main')

@section('content')
    <p class="caption">Test Player List - {{ number_format(\BFACP\Realm\Player::count()) }}</p>
@endsection
