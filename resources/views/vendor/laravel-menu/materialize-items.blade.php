@foreach($items as $item)
    @if($item->hasChildren())
        <li class="no-padding">
            <ul class="collapsible" data-collapsible="accordion">
                <li class="bold">
                    <a @lm-attrs($item) class="collapsible-header waves-effect waves-cyan @if($item->active) active @endif" @lm-endattrs>
                        <i class="material-icons">pages</i>
                        <span class="nav-text">{{ $item->title }}</span>
                    </a>
                    <div class="collapsible-body">
                        <ul>

                        </ul>
                    </div>
                </li>
            </ul>
        </li>
    @endif
@endforeach
