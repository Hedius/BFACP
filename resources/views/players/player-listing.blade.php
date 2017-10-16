@extends('template.main')

@section('content')
    <p class="caption">Test Player List - {{ \BFACP\Realm\Player::count() }}</p>
@endsection
