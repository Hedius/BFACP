var bfacp = angular.module('bfacp', ['ngResource', 'ui.materialize']);

/*=============================================
=            BFACP Factory's                  =
=============================================*/

bfacp.factory('Player', function ($resource) {
    return $resource('/api/player/:playerId', {}, {
        query: {
            method: 'GET',
            params: {playerId: '@playerId'}
        }
    });
});

/*=============================================
=            BFACP Controllers                =
=============================================*/

bfacp.controller('PlayerListController', function PlayerListController(Player, $scope) {
    $scope.players = [];
    $scope.meta = {};

    Player.query().$promise.then(function (response) {
        $scope.players = response.data;
        $scope.meta = response.meta;
    });

    $scope.changePage = function (page) {
        Player.query({page: page}).$promise.then(function (response) {
            $scope.players = response.data;
            $scope.meta = response.meta;
        });
    }
});
