<nav class="navbar navbar-static-top" role="navigation">
    <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
    <span class="sr-only">Toggle navigation</span>
    </a>
    <div class="navbar-custom-menu">
        <ul class="nav navbar-nav">
            <li class="dropdown messages-menu">
            <li class="dropdown user user-menu">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                <img src="{!! asset('template_libraries/dist/img/default_avatar.png') !!}" class="user-image" alt="User Image">
                <span class="hidden-xs">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</span>
                </a>
                <ul class="dropdown-menu">
                    <li class="user-header">
                        <img src="{!! asset('template_libraries/dist/img/default_avatar.png') !!}" class="img-circle" alt="User Image">
                        <p>
                            {{ Auth::user()->first_name }} {{ Auth::user()->last_name }} - Admin
                            <small>Created: {{ Auth::user()->created_at->diffForHumans() }}</small>
                        </p>
                    </li>
                    <li class="user-footer">
                        <div class="pull-left">
                            <a href="#" class="btn btn-default btn-flat">Profile</a>
                        </div>
                        <div class="pull-right">
                            <a href="/auth/logout" class="btn btn-default btn-flat">Sign out</a>
                        </div>
                    </li>
                </ul>
            </li>
            </li>
        </ul>
    </div>
</nav>