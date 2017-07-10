<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Barangay Project</title>
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
        <link rel="stylesheet" href="{!! asset('template_libraries/bootstrap/css/bootstrap.min.css') !!}">
        <link rel="stylesheet" href="{!! asset('https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css') !!}">
        <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
        <link rel="stylesheet" href="{!! asset('template_libraries/dist/css/AdminLTE.min.css') !!}">
        <link rel="stylesheet" href="{!! asset('template_libraries/dist/css/skins/_all-skins.min.css') !!}">
        <link rel="stylesheet" href="{!! asset('template_libraries/plugins/iCheck/flat/blue.css') !!}">
        @yield('additional_css')
    </head>
    <body class="hold-transition skin-blue sidebar-mini">
        <div class="wrapper">
            <header class="main-header">
                <a href="#" class="logo">
                    <span class="logo-mini"><b>B</b>P</span>
                    <span class="logo-lg"><b>B</b>rgy <b>P</b>roject</span>
                </a>
                @include('Templates.nav')
            </header>
            <aside class="main-sidebar">
                @include('Templates.sidebar')
            </aside>
            <div class="content-wrapper">
                <section class="content">
                    @yield('content')
                </section>
            </div>
            <footer class="main-footer">
                <strong>Copyright &copy; <?php echo date('Y'); ?>. </strong> All rights reserved.
            </footer>
            <div class="control-sidebar-bg"></div>
        </div>
        <script src="{!! asset('template_libraries/plugins/jQuery/jQuery-2.1.4.min.js') !!}"></script>
        <script src="https://code.jquery.com/ui/1.11.4/jquery-ui.min.js"></script>
        <script>
            $.widget.bridge('uibutton', $.ui.button);
        </script>
        <script src="{!! asset('template_libraries/bootstrap/js/bootstrap.min.js') !!}"></script>
        <script src="{!! asset('template_libraries/plugins/slimScroll/jquery.slimscroll.min.js') !!}"></script>
        <script src="{!! asset('template_libraries/plugins/fastclick/fastclick.min.js') !!}"></script>
        <script src="{!! asset('template_libraries/dist/js/app.min.js') !!}"></script>
        @yield('additional_js')
    </body>
</html>