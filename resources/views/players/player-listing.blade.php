@extends('template.main')

@section('content')
    <p class="caption">Test Player List - {{ number_format(\BFACP\Realm\Player::count()) }}</p>
    <table class="bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Game</th>
                <th>JSON</th>
            </tr>
        </thead>

        <tbody>
            @foreach(\BFACP\Realm\Player::take(20)->get() as $player)
                <tr>
                    <td>{{ $player->PlayerID }}</td>
                    <td>{{ $player->SoldierName }}</td>
                    <td>{{ $player->game->Name }}</td>
                    <td>{{ json_encode($player) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
