@extends('template.main')

@section('content')
    <div class="section" ng-controller="PlayerListController">
        <div class="row">
            <div class="col s12">
                <div class="right">
                    <pagination
                            page="meta.current_page"
                            page-size="meta.per_page"
                            total="meta.total"
                            show-prev-next="true"
                            use-simple-prev-next="false"
                            dots="...."
                            hide-if-empty="true"
                            adjacent="2"
                            scroll-top="true"
                            pagination-action="changePage(page)"></pagination>
                </div>
                <table class="bordered striped">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Game</th>
                        <th>Country</th>
                    </tr>
                    </thead>
                    <tbody>
                        <tr ng-repeat="player in players track by player.id">
                            <td ng-bind="player.id"></td>
                            <td><img ng-show="player.battlelog.gravatar != null" class="circle responsive-img" gravatar-src="player.battlelog.gravatar" gravatar-size="32">&nbsp;<span ng-show="player.clantag != null" class="blue-text">[@{{ player.clantag }}]&nbsp;</span>@{{ player.name }}</td>
                            <td><div class="chip" ng-class="player.game.chip_class" ng-bind="player.game.label"></div></td>
                            <td>
                                <img tooltipped ng-src="@{{ country.flag(player.meta.country_code) }}" data-position="left"
                                     data-delay="50" data-tooltip="@{{ country.name(player.meta.country_code) }}">
                            </td>
                        </tr>
                    </tbody>

                    <tfoot>
                        <tr>
                            <td colspan="3">
                                <div class="right">
                                    <pagination
                                        page="meta.current_page"
                                        page-size="meta.per_page"
                                        total="meta.total"
                                        show-prev-next="true"
                                        use-simple-prev-next="false"
                                        dots="...."
                                        hide-if-empty="true"
                                        adjacent="2"
                                        scroll-top="true"
                                        pagination-action="changePage(page)"></pagination>
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="divider"></div>
@endsection
