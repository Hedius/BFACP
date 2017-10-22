<!-- START LEFT SIDEBAR NAV-->
<aside id="left-sidebar-nav" class="nav-expanded nav-lock nav-collapsible">
    <div class="brand-sidebar">
        <h1 class="logo-wrapper">
            <a href="/" class="brand-logo darken-1">
                <img src="../../images/logo/materialize-logo.png" alt="materialize logo">
                <span class="logo-text hide-on-med-and-down">BFACP</span>
            </a>
            <a href="#" class="navbar-toggler">
                <i class="material-icons">radio_button_checked</i>
            </a>
        </h1>
    </div>

    <ul id="slide-out" class="side-nav fixed leftside-navigation">
        @include('vendor.laravel-menu.materialize', ['items' => $SideNav->roots()]))
    </ul>
    <a href="#" data-activates="slide-out" class="sidebar-collapse btn-floating btn-medium waves-effect waves-light hide-on-large-only gradient-45deg-light-blue-cyan gradient-shadow">
        <i class="material-icons">menu</i>
    </a>
</aside>
<!-- END LEFT SIDEBAR NAV-->
