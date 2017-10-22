@foreach($items as $item)
    <li @if($item->hasChildren()) class="no-padding" @endif @if($item->active) class="active" @endif>
        @if(!$item->hasChildren())
            <a class="waves-effect waves-cyan" href="{!! $item->url() !!}">
                <i class="material-icons">pages</i>
                <span class="nav-text">{{ $item->title }}</span>
            </a>
        @else
            <ul class="collapsible" data-collapsible="accordion" >
                <li @if($item->hasChildren()) class="bold @if($item->active) active @endif" @endif>
                    <a class="@if($item->hasChildren()) collapsible-header @endif waves-effect waves-cyan @if($item->active) active @endif">
                        <i class="material-icons">pages</i>
                        <span class="nav-text">{{ $item->title }}</span>
                    </a>
                    @if($item->hasChildren())
                        <div class="collapsible-body">
                            <ul>
                                @include('vendor.laravel-menu.materialize', ['items' => $item->children()])
                            </ul>
                        </div>
                    @endif
                </li>
            </ul>
        @endif
    </li>
@endforeach
