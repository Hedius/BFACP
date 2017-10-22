<!DOCTYPE html>
<html ng-app="bfacp">
    <head>
        @include('template.header')
    </head>

    <body class="layout-semi-dark">
        @include('template.body-header')

        <!-- START MAIN -->
        <div id="main">
            <!-- START WRAPPER -->
            <div class="wrapper">
                @include('template.body-left-sidebar')

                <section id="content">
                    @include('template.body-breadcrumbs')

                    <div class="container">
                        @yield('content')
                    </div>
                </section>
            </div>
            <!-- END WRAPPER -->
        </div>
        <!-- END MAIN -->
        @include('template.body-footer')

        @include('template.footer')
    </body>
</html>
